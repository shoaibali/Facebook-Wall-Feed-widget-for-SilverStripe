# Facebook Wall Feed widget for SilverStripe
Fetching your latest wall feed stream from Facebook.


## Description
You have a Facebook page and regularly post status messages, photos, links, videos etc and if you'd like to reuse these messages on your SilverStripe website, then this widget will simply get these for you (you can define the number).

Thanks to a client not wanting to use the javascript fb:like-box data-stream plugin (https://developers.facebook.com/docs/reference/plugins/like-box/) which renders in an iframe, I had to develop this to grab the same information using Facebook graph API. 

PS: This plugin will also work with individual's Facebook wall feed as well. Please note that it will only grab status messages, photos, links and videos etc post by the OWNER of the Page only. If you would like to change this you can do so by adding to ``mysite/_config.php``:
```php
FacebookFeedWidget::$show_fb_id_only = FALSE;
```

Other options:
```php
\\ Do not display status messages
FacebookFeedWidget::$show_status_messages = FALSE;
```

## Requirements
SilverStripe 3.0+.
Widgets module.


## Dependencies
Silverstripe 3.0+.
PHP 5+ (Uses PHP function ``file_get_contents`` to connect to Facebook, if you are on shared hosting please check with your hosting provider if they allow this - they might not due to security reasons).
php_curl (The GenerateFacebookAccessToken task may require cURL to be enabled).
MySQL 5+.

## Installation
1. ``allow_url_fopen`` must be enabled in your PHP configuration for this to work, otherwise you're not allowed to use Facebook API.
2. ``file_get_contents`` might require context if you are on a hosting provider that proxies your traffic (most likely) - see source code FacebookFeedWidget.php.
3. Extract the ``silverstripe-widget_facebookfeed`` folder into the top level of your site and rename it to ``widget_facebookfeed``.
4. Ensure that widgets have been enabled in your site. You should have something like the following code in ``mysite/code/Page.php`` or ``mysite/code/HomePage.php``:

```php
public static $has_one = array(
  'FacebookStream' => 'WidgetArea',
);

public function getCMSFields(){
  $fields = parent::getCMSFields();
  $fields->addFieldToTab(
	'Root.Content.Facebook',
	new WidgetAreaEditor('FacebookStream')
  );
  return $fields;
}
```
            
5. Add the placeholder ``$FacebookStream`` to your template where you want to display your Facebook feed.
6. Run ``/dev/build?flush=all``.
7. Reload the CMS interface, the widget should be usable on the *Facebook* tab.


## Configuration
A lot of people struggle with this mainly due to either lack of documentation or cryptic Facebook API documentation. Therefore I've made it as simple as possible unless of course Facebook changes their API again.

All the configuration parameters such as:
* Facebook Page ID
* Facebook App ID
* Facebook App Secret
* Facebook Access Token

…can be configured in the topmost Site Configuration (*Settings* menu) inside Facebook tab. This is where you set your Site Title, Site Default Theme, Site Slogan etc. The questions I asked myself when I was working with this widget are:

Q. How & where do I get my Facebook profile or my Page ID?
There are many ways to get your Page ID or your profile ID but following are the steps I found easy to use.

1. Make sure you are logged in to your Facebook and the Page you are trying to generate stream for you are it's administrator or have apropriate permissions.

2. Find out your username usually something like this https://facebook.com/shoaibali (where shoaibali = username). If not sure you can always go to https://www.facebook.com/settings?ref=mb (General Account Settings) to find out your username.

3. Now visit https://graph.facebook.com/your-user-name  (Add your username OR page name at the end of the URL) for a Coca-Cola page it would be https://graph.facebook.com/cocacola.

4. The first thing you will notice is an output that looks like this and as you can tell "id" is 40796308305.

*   "id": "40796308305",
*   "name": "Coca-Cola",
*   "picture": "http://profile.ak.fbcdn.net/hprofile-ak-snc4/174560_40796308305_2093137831_s.jpg",
*   "link": "https://www.facebook.com/coca-cola",
*   "likes": 42458554, 

5. So, the ID is 40796308305 and please make a note of this.

Q. How & where do I get my App ID?
In order to get your App ID you first need to create a Facebook App.

1. Visit https://developers.facebook.com/apps and you will either see a list of your existing apps or you can hit the [Create New App] button if you are registered as a developer. Registration is compulsory. 
2. Once you have created a new app you will be able to see App ID under Settings > Summary.


Q. How & where do I get my App Secret?
As above, you will also see App Secret on this page: https://developers.facebook.com/apps


Q. How do generate my Access Token?
This is a little tricky! In order to generate Access Token you need the above 3 pieces of information (Page/user ID, App Secret & App ID) - see above.

1. First you need to go to your App https://developers.facebook.com/apps and click Edit Settings.

2. You need to add App Domain which will be either your production site domain name or your development one; you will have to change this when moving site from local development to production, i.e. localhost to mysite.com.

3. Now you need to go to the section that says "Select how your app integrates with Facebook" - you need to check "Website with Facebook Login" and enter the "Site URL". Again this will need to be changed or updated as above. Otherwise you will get errors when trying to get access_token. The "Site URL" will be like http://localhost. 

4. I assume at this point you have already ran /dev/build?flush=all if not please do so. I have registered a Task in SilverStripe called "Generate your Facebook Access Token". If you now browse to http://localhost/dev/tasks or http://mysite.com/dev/tasks you will see it at the bottom. Please click it to generate access_token. Make sure you are logged in to Facebook and you have the appropriate permissions for the Facebook App and/or the Facebook Page you are trying to get access_token for.

5. You can also go directly to /dev/tasks/GenerateFacebookAccessToken/ to start generating an access_token.

Q. I have configured all the information but my Feed is not displaying?
If you added a facebook widget to HomePage please make sure you go to your HomePage in the CMS and enable or add the widget. Also wherever you would like to display the Feed you must call $FacebookStream variable or whatever you have called it in getCMSFields function. Also check in your SiteConfiguration that all the information is present such ass FacebookPageID, FacebookAppID, FacebookAcessToken  and FacebookAppSecret.



## TODO
- Add more configuration options to control the feed such as open links in new window for comments, verify SSL peer for curl, update access_token on expiry and be able to configure either to display posts from owner of the page or everyone.
- offline_access is being deprecated therefore Task will need to be updated accordingly see https://developers.facebook.com/roadmap/offline-access-removal/
- Support for sites running https protocol.
- Complete the CSS, currently broken.


## License
    Copyright (c) 2012, Shoaib Ali
    All rights reserved.
   
    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:
        * Redistributions of source code must retain the above copyright
          notice, this list of conditions and the following disclaimer.
        * Redistributions in binary form must reproduce the above copyright
          notice, this list of conditions and the following disclaimer in the
          documentation and/or other materials provided with the distribution.
        * Neither the name of the authors nor the names of its contributors
          may be used to endorse or promote products derived from this
          software without specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS "AS IS" AND ANY
    EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY
    DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.