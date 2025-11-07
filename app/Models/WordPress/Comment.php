<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $connection = 'mysql';
    protected $table = 'wp_comments';
    protected $primaryKey = 'comment_ID';
    public $timestamps = false;

    protected $fillable = [
        'comment_post_ID',
        'comment_author',
        'comment_author_email',
        'comment_author_url',
        'comment_author_IP',
        'comment_date',
        'comment_date_gmt',
        'comment_content',
        'comment_karma',
        'comment_approved',
        'comment_agent',
        'comment_type',
        'comment_parent',
        'user_id',
    ];

    protected $casts = [
        'comment_date' => 'datetime',
        'comment_date_gmt' => 'datetime',
        'comment_post_ID' => 'integer',
        'comment_parent' => 'integer',
        'user_id' => 'integer',
        'comment_karma' => 'integer',
    ];

    /**
     * Пост, к которому относится комментарий
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'comment_post_ID', 'ID');
    }

    /**
     * Автор комментария (если зарегистрированный пользователь)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * Родительский комментарий (для ответов)
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'comment_parent', 'comment_ID');
    }

    /**
     * Дочерние комментарии (ответы)
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'comment_parent', 'comment_ID');
    }

    /**
     * Проверка, одобрен ли комментарий
     */
    public function isApproved(): bool
    {
        return $this->comment_approved === '1';
    }

    /**
     * Проверка, в спаме ли комментарий
     */
    public function isSpam(): bool
    {
        return $this->comment_approved === 'spam';
    }

    /**
     * Проверка, в корзине ли комментарий
     */
    public function isTrashed(): bool
    {
        return $this->comment_approved === 'trash';
    }
}

