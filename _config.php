<?php
// SilverStripe 2.3 compatability

if(class_exists("SiteConfig")){
    DataObject::add_extension('SiteConfig', 'FacebookConfig');
} else {

define('FACEBOOK_PAGE_ID', '< your facebook page id >');
define('FACEBOOK_APP_ID', '< your facebook app id >');
define('FACEBOOK_APP_SECRET', '< your facebook app secret >');
define('FACEBOOK_ACCESS_TOKEN', '< your facebook Access Token from /dev/task >');

}


