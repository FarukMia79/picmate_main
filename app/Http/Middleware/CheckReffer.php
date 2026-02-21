<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckReffer
{

    public function handle($request, Closure $next)
    {
        // ১. যদি প্রজেক্ট লোকালহোস্টে (localhost) চলে, তবে কোনো চেক করবে না সরাসরি সাইট ওপেন হবে।
        if (app()->environment('local')) {
            return $next($request);
        }

        // ২. লাইভ সার্ভারের জন্য ডোমেইন চেক।
        // এটি আপনার .env ফাইল থেকে ALLOWED_DOMAIN এর নাম খুঁজে নিবে।
        $allowedDomain = env('ALLOWED_DOMAIN');
        $host = $request->getHost();

        // যদি ডোমেইন সেট করা থাকে এবং বর্তমান হোস্টের সাথে মিলে যায়, তবেই সাইট চলবে।
        if ($allowedDomain && $host && strpos($host, $allowedDomain) !== false) {
            return $next($request);
        }

        // যদি ডোমেইন না মিলে, তবে Unauthorized access এরর দিবে।
        abort(403, 'Unauthorized access.');
    }
}
