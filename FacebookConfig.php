<?php
  
class FacebookConfig extends DataObjectDecorator {
     
    function extraStatics() {
        return array(
            'db' => array(
                'FacebookPageID' => 'Varchar(255)',
                'FacebookAppID' => 'Varchar(255)',
                'FacebookAppSecret' => 'Varchar(255)',
                'FacebookAccessToken' => 'Varchar(255)'
            ),
            
            'has_one' => array()
        );
    }
  
    public function updateCMSFields(FieldSet &$fields) {
         
        $fields->addFieldToTab("Root.Facebook", new TextField("FacebookPageID"));
        $fields->addFieldToTab("Root.Facebook", new TextField("FacebookAppID"));
        $fields->addFieldToTab("Root.Facebook", new TextField("FacebookAppSecret"));
        $fields->addFieldToTab("Root.Facebook", new TextField("FacebookAccessToken"));
    }
     

}