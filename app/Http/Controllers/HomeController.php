<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use vendor\autoload;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Google_Client; 
use Google_Service_Analytics;

class HomeController extends Controller
{
    public function index()
    {
        return view('admin.home');
    }

    public function getAnalyticsSummary()
    {
        $analytics = HomeController::initializeAnalytics();
        $profile = HomeController::getFirstProfileId($analytics);
        
        //get final result in $web_data
        $web_data = HomeController::getResults($analytics, $profile);
        
        //return view('analytics',compact('web_data'));
    } 
    function initializeAnalytics()
    {
        // Creates and returns the Analytics Reporting service object.
    
        // Use the developers console and download your service account
        // credentials in JSON format. Place them in this directory or
        // change the key file location if necessary.
        $KEY_FILE_LOCATION =  app_path('analytics/beacon-credentials.json');
    
        // Create and configure a new client object.
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        
        $analytics = new Google_Service_Analytics($client);
        return $analytics;
    }
        
    function getFirstProfileId($analytics) {
        // Get the user's first view (profile) ID.
    
        // Get the list of accounts for the authorized user.
        $accounts = $analytics->management_accounts->listManagementAccounts();
        if (count($accounts->getItems()) > 0) {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();
        
            // Get the list of properties for the authorized user.
            $properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);
    
            if (count($properties->getItems()) > 0) {
                $items = $properties->getItems();
                $firstPropertyId = $items[0]->getId();
        
                // Get the list of views (profiles) for the authorized user.
                $profiles = $analytics->management_profiles
                    ->listManagementProfiles($firstAccountId, $firstPropertyId);
    
                if (count($profiles->getItems()) > 0) {
                    $items = $profiles->getItems();
                    // Return the first view (profile) ID.
                    return $items[0]->getId();
        
                } else {
                    throw new Exception('No views (profiles) found for this user.');
                }
            } else {
                throw new \Exception('No properties found for this user.');
            }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }
        
    function getResults($analytics, $profileId) {
        // Calls the Core Reporting API and queries for the number of sessions
        // for the last seven days.
        $from_date = date("Y-m-d", strtotime('7daysago'));
        $to_date = date("Y-m-d",strtotime('today')) ;

        $events = $analytics->data_ga->get(
            'ga:' . $profileId,
            $from_date,
            $to_date,
            'ga:totalEvents',
            ['metrics' => 'ga:totalEvents',
            'dimensions' => 'ga:eventaction,ga:eventCategory,ga:pagePath,ga:pageTitle,ga:date,ga:dimension1',
            'filters' => 'ga:pagePath%3D@silverpage-category,ga:pagePath%3D@silverpage']);  
        if($events != null){
            
            if (count($events->getRows()) > 0) {
                // Get the profile name.
                //$profileName = $results[0]->getProfileInfo()->getProfileName();
            
                // Get the entry for the first entry in the first row.
                $event = $events->getRows();
                
                //$event[0][0] = redirect url
                //$event[0][1] = event category
                //$event[0][2] = page path
                //$event[0][3] = Advertise title
                //$event[0][4] = Date
                //$event[0][5] = IP
                //$event[0][6] = no of event from that ip

                $no_of_event = count($event);
                for($i=0;$i<$no_of_event;$i++)
                {
                    $advertise = explode('|',$event[$i][3],2);
                    $event[$i][3] =$advertise[0];
                    $user_ip = explode(',',$event[$i][5],2);
                    $event[$i][5] = $user_ip[0];
                }           
                return $event;
            } else {
                return $event = "No results found.\n";
            }
        } else {
            return $event = "No data found.\n";
        }
    }
   
}
