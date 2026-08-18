<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicFormController extends Controller
{
    public function newsletter(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        DB::table('newsletter_subscribers')->updateOrInsert(['email' => strtolower($data['email'])], ['subscribed_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
        return back()->with('success', 'You are subscribed to job updates.');
    }

    public function contact(Request $request)
    {
        $data = $request->validate(['name' => 'required|max:120', 'email' => 'required|email|max:255', 'phone' => 'nullable|max:30', 'message' => 'required|max:3000', 'job_listing_id' => 'nullable|exists:job_listings,id']);
        DB::table('contact_messages')->insert($data + ['status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Your message has been sent.');
    }
}
