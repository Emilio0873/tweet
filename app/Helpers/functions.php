<?php

use App\Helpers\Security;
use App\Helpers\Url;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return Security::e($value);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return Url::to($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Url::asset($path);
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path): string
    {
        if (!$path) {
            return asset('images/default-avatar.svg');
        }
        return Url::upload($path);
    }
}

if (!function_exists('initials')) {
    function initials(string $prenom, string $nom): string
    {
        return mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $datetime): string
    {
        if (!$datetime) {
            return 'Jamais';
        }
        $ts = strtotime($datetime);
        $diff = time() - $ts;
        if ($diff < 60) return 'À l\'instant';
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' j';
        return date('d/m/Y', $ts);
    }
}

if (!function_exists('render_content')) {
    function render_content(?string $text): string
    {
        $safe = e($text ?? '');
        $safe = preg_replace_callback('/#([\p{L}\p{N}_]+)/u', function (array $m): string {
            $tag = $m[1];
            return '<a class="hash" href="' . url('hashtag/' . rawurlencode($tag)) . '">#' . $tag . '</a>';
        }, $safe);
        $safe = preg_replace_callback('/@([a-zA-Z0-9._]+)/', function (array $m): string {
            $name = $m[1];
            return '<a class="mention" href="' . url('u/' . rawurlencode($name)) . '">@' . $name . '</a>';
        }, $safe);
        $safe = preg_replace(
            '~(https?://[^\s<]+)~',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $safe
        );
        return nl2br($safe, false);
    }
}

if (!function_exists('money')) {
    function money($amount): string
    {
        if ($amount === null || $amount === '') {
            return 'Sur demande';
        }
        return number_format((float) $amount, 0, ',', ' ') . ' DH';
    }
}

if (!function_exists('visibility_label')) {
    function visibility_label(string $value): string
    {
        return [
            'public'    => 'Public',
            'friends'   => 'Amis',
            'followers' => 'Abonnés',
            'private'   => 'Privé',
        ][$value] ?? 'Public';
    }
}
