<?php
GFForms::include_feed_addon_framework();

class GFi360 extends GFFeedAddOn {

    protected $_version = GF_I360_VERSION;
    protected $_min_gravityforms_version = '1.9.16';
    protected $_slug = 'i360';
    protected $_path = 'gravity-forms-i360/class-gf-i360.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms: i360';
    protected $_short_title = 'i360 Integration';

    private static $_instance = null;

    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new GFi360();
        }

        return self::$_instance;
    }

    public function init() {
        parent::init();
    }

    public function process_feed( $feed, $entry, $form ) {
        $contact = array('contacts'=>array());

        $contact_fields = array();
        /* Create JSON from fields */
        $contact_info = $this->get_field_map_fields($feed,'i360_contact');
        foreach($contact_info as $field_name => $field_id){
            if($field_name == 'category' || $field_name == 'tag'){
                $field_value = $this->get_field_value($form,$entry,$field_id);
                if($field_value){$contact_fields['tags'][$field_name] = $field_value;}
            }else{
                $field_value = $this->get_field_value($form,$entry,$field_id);
                if($field_value){$contact_fields[$field_name] = $field_value;}
            }
        }

        array_push($contact['contacts'],$contact_fields);

        $debug = $this->get_plugin_setting( 'i360_debug');

        /* i360 Contact Import */
        $orgID = $this->get_plugin_setting( 'i360_orgid');
        $username = $this->get_plugin_setting( 'i360_username');
        $password = $this->get_plugin_setting( 'i360_password');
        $basicAuthHeader =  'Basic ' . base64_encode('roclient:secret');
        $token_url = 'https://login.i-360.com/core/connect/token';
        $data = array('grant_type' => 'password', 'username' => $username, 'password' => $password, 'scope'=>'openid profile sampleApi');
        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\nAuthorization: ".$basicAuthHeader,
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($token_url, false, $context);
        $data = json_decode($result);
        $token = $data->{'access_token'};
        if($token){
            $requestUrl = 'https://api-platform.i-360.com/2.0/Org/'.$orgID.'/Contacts';
            $options = array(
              'http' => array(
                'method'  => 'POST',
                'content' => http_build_query($contact),
                'header'=>  "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\nContent-Length: ".strlen(http_build_query($contact))."\r\nAuthorization: Bearer ".$token,
                )
            );
            $context  = stream_context_create( $options );
            $result = file_get_contents( $requestUrl, false, $context );
            if($debug){echo '<pre>';var_dump($result).'</pre>';}
        }
    }


    public function plugin_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'i360 Settings', 'i360' ),
                'fields' => array(
                    array(
                        'label'             => esc_html__( 'API Username', 'i360' ),
                        'type'              => 'text',
                        'name'              => 'i360_username',
                        'tooltip'           => esc_html__( 'Username for your i360 account', 'i360' ),
                        'class'             => 'medium',
                        //'feedback_callback' => array( $this, 'is_valid_setting' ),
                    ),
                    array(
                        'label'             => esc_html__( 'API Password', 'i360' ),
                        'type'              => 'text',
                        'name'              => 'i360_password',
                        'tooltip'           => esc_html__( 'Password for your i360 account', 'i360' ),
                        'class'             => 'medium',
                        //'feedback_callback' => array( $this, 'is_valid_setting' ),
                    ),
                    array(
                        'label'             => esc_html__( 'Org ID', 'i360' ),
                        'type'              => 'text',
                        'name'              => 'i360_orgid',
                        'tooltip'           => esc_html__( 'OrgID for your i360 account', 'i360' ),
                        'class'             => 'small',
                        //'feedback_callback' => array( $this, 'is_valid_setting' ),
                    ),
                    array(
                        'label'             => esc_html__( 'Debug', 'i360' ),
                        'type'              => 'checkbox',
                        'name'              => 'i360_debug',
                        'tooltip'           => esc_html__( 'Displays the response from the i360 API', 'i360' ),
                        'choices'           => array(
                            array(
                                'label'     => esc_html( 'Enabled', 'i360' ),
                                'name'      => 'i360_debug'
                            ),
                        ),
                        //'feedback_callback' => array( $this, 'is_valid_setting' ),
                    ),
                )
            )
        );
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'i360 Settings', 'i360' ),
                'fields' => array(
                    array(
                        'label'   => esc_html__( 'Feed name', 'i360' ),
                        'type'    => 'text',
                        'name'    => 'feedName',
                        'tooltip' => esc_html__( 'Name of the feed. You can use multiple feeds to create different contacts', 'i360' ),
                        'class'   => 'small',
                    ),
                    array(
                        'name'      => 'i360_contact',
                        'label'     => esc_html__( 'i360 Contact Fields', 'i360' ),
                        'type'      => 'field_map',
                        'field_map' => array(
                            array(
                                'name'          => 'salutation',
                                'label'         => esc_html__( 'Salutation', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'name', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'name', 3 ),
                            ),
                            array(
                                'name'          => 'firstName',
                                'label'         => esc_html__( 'First Name', 'i360' ),
                                'required'      => true,
                                'field_type'    => array( 'name', 'text', 'hidden' ),
                                'default_value' => $this->get_first_field_by_type( 'name', 3 ),
                            ),
                            array(
                                'name'          => 'middleName',
                                'label'         => esc_html__( 'Middle Name', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'name', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'name', 3 ),
                            ),
                            array(
                                'name'          => 'lastName',
                                'label'         => esc_html__( 'Last Name', 'i360' ),
                                'required'      => true,
                                'field_type'    => array( 'name', 'text', 'hidden' ),
                                'default_value' => $this->get_first_field_by_type( 'name', 6 ),
                            ),
                            array(
                                'name'          => 'suffix',
                                'label'         => esc_html__( 'Suffix', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'name', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'name', 6 ),
                            ),
                            array(
                                'name'          => 'email',
                                'label'         => esc_html__( 'Email Address', 'i360' ),
                                'required'      => true,
                                'field_type'    => array( 'email', 'hidden' ),
                                'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'email2',
                                'label'         => esc_html__( 'Email Address 2', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'email', 'hidden' ),
                                '//default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'phone',
                                'label'         => esc_html__( 'Phone Number', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'phone', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'phone2',
                                'label'         => esc_html__( 'Phone Number 2', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'phone', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'phone3',
                                'label'         => esc_html__( 'Phone Number 3', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'phone', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'address1',
                                'label'         => esc_html__( 'Address', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'address', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'address2',
                                'label'         => esc_html__( 'Address2', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'address', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'city',
                                'label'         => esc_html__( 'City', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'address', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'state',
                                'label'         => esc_html__( 'State', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'address', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'zip',
                                'label'         => esc_html__( 'Zip Code', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'address', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'countyName',
                                'label'         => esc_html__( 'County', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'select', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'employer',
                                'label'         => esc_html__( 'Employer', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'occupation',
                                'label'         => esc_html__( 'Occupation', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'gender',
                                'label'         => esc_html__( 'Gender', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'race',
                                'label'         => esc_html__( 'Race', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'select', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'ethnicity',
                                'label'         => esc_html__( 'Ethnicity', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'select', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'religion',
                                'label'         => esc_html__( 'Religion', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'dateOfBirth',
                                'label'         => esc_html__( 'Date of Birth', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'date', 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'category',
                                'label'         => esc_html__( 'Category', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                            array(
                                'name'          => 'tag',
                                'label'         => esc_html__( 'Tag', 'i360' ),
                                'required'      => false,
                                'field_type'    => array( 'text', 'hidden' ),
                                //'default_value' => $this->get_first_field_by_type( 'email' ),
                            ),
                        ),
                    ),
                    array(
                        'name'           => 'condition',
                        'label'          => esc_html__( 'Condition', 'i360' ),
                        'type'           => 'feed_condition',
                        'checkbox_label' => esc_html__( 'Enable Condition', 'i360' ),
                        'instructions'   => esc_html__( 'Process this simple feed if', 'i360' ),
                    ),
                ),
            ),
        );
    }

    public function feed_list_columns() {
        return array(
            'feedName'  => esc_html__( 'Name', 'i360' ),
        );
    }

    public function can_create_feed() {return true;}
}