<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'year',
        'publisher',
        'city',
        'bookshelf_id',
        'cover',
        'content_pdf'
    ];

    public function bookshelf(): BelongsTo
    {
        return $this->belongsTo(Bookshelf::class);
    }

    public static function export()
    {
        $books = Book::with('bookshelf')->get();
        $books_filter = [];
        $no = 1;
        for ($i = 0; $i < $books->count(); $i++) {
            $books_filter[$i]['no'] = $no++;
            $books_filter[$i]['title'] = $books[$i]->title;
            $books_filter[$i]['author'] = $books[$i]->author;
            $books_filter[$i]['year'] = $books[$i]->year;
            $books_filter[$i]['publisher'] = $books[$i]->publisher;
            $books_filter[$i]['bookshelf'] = $books[$i]->bookshelf->name;
        }
        return $books_filter;
    }
}
