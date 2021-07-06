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
        $results = HomeController::getResults($analytics, $profile);
        $web_data = HomeController::printResults($results);
        return view('analytics',compact('web_data'));
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
        $from_date = date("Y-m-d", strtotime('06/09/2021'));
        $to_date = date("Y-m-d",strtotime('06/09/2021')) ;

        $events[0] = $analytics->data_ga->get(
            'ga:' . $profileId,
            $from_date,
            $to_date,
            'ga:totalevents',
            ['metrics' => 'ga:totalEvents',
            'dimensions' => 'ga:eventaction,ga:eventCategory',
            'filters' => 'ga:pagePath==/silver-pages-directory']);
            
        $events[1] = $analytics->data_ga->get(
                'ga:' . $profileId,
                $from_date,
                $to_date,
                'ga:users,ga:sessions,ga:pageviews');

        $events[2] = $analytics->data_ga->get(
            'ga:' . $profileId,
            $from_date,
            $to_date,
            'ga:sessions',
            ['metrics' => 'ga:sessions',
            'dimensions' => 'ga:dimension1' ]);
           
            return $events;    
    }
        
    function printResults($results) {
        
        // Parses the response from the Core Reporting API and prints
        // the profile name and total sessions.
        if (count($results[0]->getRows()) > 0) {
    
            // Get the profile name.
            $profileName = $results[0]->getProfileInfo()->getProfileName();
        
            // Get the entry for the first entry in the first row.
            $event = $results[0]->getRows();
            
            $data = $results[1]->getRows();
            //$users = $rows[0][0];
            //$sessions = $rows[0][1];
            $web_data[0] = $event[0][0];
            $web_data[1] = $event[0][1];
            
            $web_data[2] = $event[0][2];
          
            $web_data[3] = $data[0][0];
            $web_data[4] = $data[0][1];
            $web_data[5] = $data[0][2];
            
            // Print the results.
            //print "First view (profile) found: $profileName\n";
            //print "Total user: $users\n";
            //print "Total session: $sessions\n";
            //print "Total pageviews of page: $pagename = $pageviews\n";
            return $web_data;
        } else {
            print "No results found.\n";
        }
    }
   
}
