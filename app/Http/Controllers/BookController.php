<?php

namespace App\Http\Controllers;

use App\Exports\BooksExport;
use App\Imports\BooksImport;
use App\Models\Book;
use App\Models\Bookshelf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class BookController extends Controller
{
    public function index()
    {
        $data['books'] = Book::with('bookshelf')->get();
        return view('books.index', $data);
    }

    public function create()
    {
        $data['bookshelves'] = Bookshelf::pluck('name', 'id');
        return view('books.create', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'author' => 'required|max:150',
            'year' => 'required|digits:4|integer|min:1900|max:' . (date('Y')),
            'publisher' => 'required|max:100',
            'city' => 'required|max:75',
            'bookshelf_id' => 'required',
            'cover' => 'nullable|image',
            'content_pdf' => 'nullable|mimes:pdf|max:10240'

        ]);

        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->storeAs(
                'public/cover_buku',
                'cover_buku_' . time() . '.' . $request->file('cover')->extension()
            );
            $validated['cover'] = basename($path);
        }

        $fileName = time() . '_' . $request->file('content_pdf')->getClientOriginalName();
        $path = $request->file('content_pdf')->storeAs(
            'public/pdf_books',
            $fileName
        );
        $validated['content_pdf'] = basename($path);


        // Simpan buku ke database
        Book::create($validated);
        // dd(Book::latest()->first());
        // dd($validated);

        $notification = array(
            'message' => 'Data buku berhasil ditambahkan',
            'alert-type' => 'success'
        );

        if ($request->save == true) {
            return redirect()->route('book')->with($notification);
        } else {
            return redirect()->route('book.create')->with($notification);
        }
    }


    public function edit(string $id)
    {
        $data['book'] = Book::find($id);
        $data['bookshelves'] = Bookshelf::pluck('name', 'id');
        return view('books.edit', $data);
    }

    public function update(Request $request, string $id)
    {
        $book = Book::find($id);

        $validated = $request->validate([
            'title' => 'required|max:255',
            'author' => 'required|max:150',
            'year' => 'required|digits:4|integer|min:1900|max:' . (date('Y')),
            'publisher' => 'required|max:100',
            'city' => 'required|max:75',
            'bookshelf_id' => 'required',
            'cover' => 'nullable|image',
            'content_pdf' => 'nullable|mimes:pdf|max:10240'

        ]);

        if ($request->hasFile('cover')) {
            // Hapus cover lama jika ada
            if ($book->cover != null) {
                Storage::delete('public/cover_buku/' . $book->cover);
            }
            $path = $request->file('cover')->storeAs(
                'public/cover_buku',
                'cover_buku_' . time() . '.' . $request->file('cover')->extension()
            );
            $validated['cover'] = basename($path);
        }

        $fileName = time() . '_' . $request->file('content_pdf')->getClientOriginalName();
        $path = $request->file('content_pdf')->storeAs(
            'public/pdf_books',
            $fileName
        );
        $validated['content_pdf'] = basename($path);

        

        // Update buku ke database
        Book::where('id', $id)->update($validated);

        $notification = array(
            'message' => 'Data buku berhasil diperbaharui',
            'alert-type' => 'success'
        );

        return redirect()->route('book')->with($notification);

    }

    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);
        $book->delete();

        Storage::delete('public/cover_buku/' . $book->cover);

        $notification = array(
            'message' => 'Data buku berhasil dihapus',
            'alert-type' => 'success'
        );
        return redirect()->route('book')->with($notification);
    }

    public function print()
    {
        $data['books'] = Book::all();
        $pdf = Pdf::loadView('books.print', $data);
        return $pdf->stream('books.pdf');
    }

    public function export()
    {
        return Excel::download(new BooksExport, 'books.xlsx');
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|max:10000|mimes:xlsx,xls',
        ]);
        Excel::import(new BooksImport, $request->file('file'));
        $notification = array(
            'message' => 'Import data berhasil dilakukan',
            'alert-type' => 'success'
        );
        return redirect()->route('book')->with($notification);
    }
}