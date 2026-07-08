<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Configuration
    |--------------------------------------------------------------------------
    |
    | Laravel's built-in CSRF protection uses a token that is generated and stored
    | in the user's session. This configuration allows you to customize how tokens
    | are handled for your application, including custom names and expiration policies.
    */

    /**
     * Determines the name of the session cookie used for storing CSRF tokens.
     * By default, Laravel uses '_token'. You can override this with a custom value
     * if needed (e.g., for multi-tenant applications or specific security requirements).
     */
    'token' => env('CSRF_TOKEN', '_token'),

    /**
     * Determines the name of the CSRF token in HTML forms.
     * This is used when generating form fields with Laravel's csrf_field() helper.
     */
    'form_token_name' => env('CSRF_FORM_TOKEN_NAME', '_token'),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Expiration Time (in seconds)
    |--------------------------------------------------------------------------
    |
    * Laravel's built-in CSRF tokens are typically tied to the session lifetime.
     * This configuration option allows you to set a custom expiration time for tokens,
     * which is useful when using database-backed sessions or implementing token-based auth.
     */
    'token_expire' => env('CSRF_TOKEN_EXPIRE', 60 * 60), // Default: 1 hour

    /**
     * Determines if CSRF tokens should expire on session close (browser closed).
     * When using database-backed sessions, this ensures tokens are invalidated when the user logs out.
     */
    'token_expire_on_close' => env('CSRF_TOKEN_EXPIRE_ON_CLOSE', true),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Hash Key (for encrypted storage)
    |--------------------------------------------------------------------------
    |
     * When using database-backed sessions with encryption, this key is used to hash/encrypt tokens.
     */
    'hash_key' => env('CSRF_TOKEN_HASH_KEY', null), // Set via ENV or leave as null

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Same-Site Policy
    |--------------------------------------------------------------------------
    |
     * This setting controls the SameSite attribute for session cookies, which helps prevent CSRF attacks.
     */
    'same_site' => env('CSRF_SAME_SITE_COOKIE', 'lax'),

    /**
     * Determines if partitioned cookies should be used (Chrome 126+).
     * Partitioned cookies are accepted by the browser when flagged "secure" and SameSite is set to "none".
     */
    'partitioned' => env('CSRF_PARTITIONED_COOKIE', false),

];
