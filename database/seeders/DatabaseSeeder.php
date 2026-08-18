<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\JobCategory;
use App\Models\Company;
use App\Models\JobListing;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        foreach ([['Technology','technology'],['Commerce','commerce'],['Hotels & Tourism','hotels-tourism'],['Education','education'],['Financial Services','financial-services'],['Construction','construction']] as [$name,$slug]) JobCategory::firstOrCreate(['slug'=>$slug],['name'=>$name]);
        foreach ([['Free',0,1],['Growth',4900,10],['Business',12900,50]] as [$name,$price,$credits]) Plan::firstOrCreate(['name'=>$name],['price_cents'=>$price,'job_credits'=>$credits,'features'=>['Dummy checkout','Job credits','Employer analytics']]);
        foreach ([['Admin User','admin@example.com','admin'],['Employer User','employer@example.com','employer'],['Candidate User','candidate@example.com','candidate']] as [$name,$email,$role]) User::firstOrCreate(['email'=>$email],['name'=>$name,'role'=>$role,'email_verified_at'=>now(),'password'=>Hash::make('password')]);

        $employer = User::where('email', 'employer@example.com')->firstOrFail();
        $company = Company::updateOrCreate(['slug' => 'hummingbed-talent'], [
            'owner_id' => $employer->id, 'name' => 'Hummingbed Talent', 'email' => 'jobs@hummingbed.test',
            'website' => 'https://hummingbed.test', 'industry' => 'Technology', 'size' => '51-200',
            'city' => 'Lagos', 'country' => 'Nigeria', 'description' => 'A modern talent company connecting ambitious people with meaningful work.',
            'verification_status' => 'verified', 'is_featured' => true,
        ]);
        $company->members()->syncWithoutDetaching([$employer->id => ['role' => 'owner']]);

        $categories = JobCategory::pluck('id', 'slug');
        $jobs = [
            ['Corporate Solutions Executive','corporate-solutions-executive','commerce','Lagos','Nigeria',40000,42000],
            ['Senior Frontend Engineer','senior-frontend-engineer','technology','Remote','Nigeria',65000,85000],
            ['Customer Success Manager','customer-success-manager','commerce','Abuja','Nigeria',35000,48000],
            ['Hotel Operations Lead','hotel-operations-lead','hotels-tourism','Lagos','Nigeria',45000,60000],
            ['Finance Operations Analyst','finance-operations-analyst','financial-services','Lagos','Nigeria',38000,52000],
            ['Learning Experience Designer','learning-experience-designer','education','Remote','Nigeria',42000,58000],
        ];
        foreach ($jobs as [$title,$slug,$category,$city,$country,$minimum,$maximum]) {
            JobListing::updateOrCreate(['slug' => $slug], [
                'company_id' => $company->id, 'created_by' => $employer->id, 'job_category_id' => $categories[$category],
                'title' => $title, 'description' => "Join a collaborative team solving practical problems for growing organisations. You will own meaningful work, partner across functions, and help improve how our customers succeed.",
                'responsibilities' => 'Own key projects, communicate progress, and continuously improve delivery.',
                'requirements' => 'Strong communication, sound judgement, and relevant professional experience.',
                'employment_type' => 'full_time', 'workplace_type' => $city === 'Remote' ? 'remote' : 'hybrid',
                'experience_level' => 'Mid level', 'education_level' => 'Bachelor', 'city' => $city, 'country' => $country,
                'salary_min' => $minimum, 'salary_max' => $maximum, 'currency' => 'USD', 'salary_visible' => true,
                'application_type' => 'internal', 'status' => 'published', 'published_at' => now(), 'application_deadline' => now()->addDays(45),
            ]);
        }
    }
}
