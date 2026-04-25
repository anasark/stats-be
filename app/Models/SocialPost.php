<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only Eloquent model for any per-client social platform table.
 *
 * Each client's data lives in its own PostgreSQL schema (named after the
 * user's `name` field). Within that schema there is one table per platform:
 * facebook, instagram, tiktok, twitter, etc.
 *
 * Column layout:
 *   id          integer primary key
 *   post_id     varchar   — platform-native post identifier
 *   keyword     varchar   — the monitored search keyword (e.g. 'banjir')
 *   content     text      — main text (duplicated from metrics for full-text search)
 *   metrics     jsonb     — full raw API payload (platform-specific structure)
 *   created_at  timestamp
 *
 * Usage:
 *   SocialPost::forClient('client_a', 'facebook')->where(...)->get();
 */
class SocialPost extends Model
{
    protected $connection = 'pgsql';

    public $timestamps = false;

    protected $casts = [
        'metrics'    => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Return a query builder scoped to the given client schema and platform table.
     *
     * @param  string  $schema    PostgreSQL schema name (= authenticated user's name)
     * @param  string  $platform  Table name: facebook | instagram | tiktok | twitter
     */
    public static function forClient(string $schema, string $platform): \Illuminate\Database\Eloquent\Builder
    {
        return (new static())
            ->setTable("\"{$schema}\".\"{$platform}\"")
            ->newQuery();
    }
}

