<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Job;
use App\User;
use App\Company;
use App\Apply;


class PagesController extends Controller
{
  public function showJobs(Request $request){
        return view('home')->with('jobs', $this->getAllJobs($request));
    }

    private function getAllJobs(Request $request){
      return Job::orderBy('updated_at', 'desc')->get();
    }


    public function getJobs(Request $request){
      $jobs = Job::orderBy('updated_at', 'desc');

      // Apply filters
      if(sizeof($request->input('categories')) > 0){
        $jobs->whereIn('category_id', array_values($request->input('categories')));
      }
      /*
      if(sizeof($request->input('regions')) > 0){
        $jobs->whereIn('region_id', array_values($request->input('regions')));
      }
      */

      if(sizeof($request->input('types')) > 0){
        $jobs->whereIn('job_type_id', array_values($request->input('types')));
      }

      if(sizeof($request->input('degrees')) > 0){
        $jobs->whereIn('degree_id', array_values($request->input('degrees')));
      }

      if(sizeof($request->input('home')) > 0){
        $jobs->whereIn('home', array_values($request->input('home')));
      }

      return view('inc.job-list')->with('jobs', $jobs->get());
    }


    public function showCompanies(){
      $companies = Company::orderBy('name', 'asc')->get();
      return view('companies')->with('companies', $companies);
    }

    public function job($id){
      $job = Job::find($id);
      if(Auth::guard('web')->check()){
        $applied = $job->isApplied(Auth::guard('web')->user()->id);
      }
      else{
        $applied = false;
      }
      return view('job-details')->with(['job' => $job, 'applied' => $applied]);
    }

    public function jobApply($job_id){
      $job = Job::find($job_id);
      if(Auth::guard('web')->check()){
        $user_id = Auth::guard('web')->user()->id;
        $applied = $job->isApplied($user_id);
        if(!$applied){
          $apply = Apply::create([
            'user_id' => $user_id,
            'job_id' => $job_id,
          ]);

          // Send Mail
          Mail::send('email.new-apply', ['apply' => $apply], function($message) use ($apply) {
            $message->subject("Nova prijava na oglas");
            $message->from('noreply@jobr.linyoon.com', 'Jobr');
            $message->to($apply->job->company->email);
          });

        }else{
          $apply = Apply::where('user_id', '=', $user_id)->where('job_id', '=', $job_id);
          $apply->delete();
        }
        return view('job-details')->with(['job' => $job, 'applied' => !$applied]);
      }
      return redirect(route('login.user'));
    }

    public function companyProfile($id){
      $company = Company::find($id);
      return view('company-profile')->with('company', $company);
    }
}
