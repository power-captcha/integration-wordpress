<?php
namespace Power_Captcha_WP {

    use WP_Error;
    use stdClass;
    
    defined('POWER_CAPTCHA_PATH') || exit;
    
    // create / init update check
    new Update_Check;
    
    // based on https://rudrastyh.com/wordpress/self-hosted-plugin-update.html and 
    //          https://github.com/rudrastyh/misha-update-checker/blob/main/misha-update-checker.php
    class Update_Check {
        
        const UPDATE_INFO_URL = 'https://power-captcha.com/wp-content/uploads/power-captcha-plugin/update-info.php';
        const CURRENT_VERSION = POWER_CAPTCHA_PLUGIN_VERSION;

        private $plugin;
        private $plugin_slug;
        private $cache_key;
        private $cache_allowed;
        private $cache_seconds;

        public function __construct() {
            $this->plugin = plugin_basename( POWER_CAPTCHA_PLUGIN_FILE ); // power-catpcha/power-captcha.php
            $this->plugin_slug = plugin_basename( POWER_CAPTCHA_PLUGIN_DIR ); // power-captcha
            $this->cache_key = 'powercaptcha_update_info_cache';
            $this->cache_allowed = true;
            $this->cache_seconds = HOUR_IN_SECONDS * 6; // cache duration: 6 hours

            add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
            add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
            add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

        }

        private function fetch_update_info() {

            $update_info = get_transient( $this->cache_key );

            if( false === $update_info || ! $this->cache_allowed ) {

                $response = wp_remote_get(
                    self::UPDATE_INFO_URL,
                    array(
                        'timeout' => 10,
                        'headers' => array(
                            'Accept' => 'application/json'
                        )
                    )
                );

                if( is_wp_error( $response ) ) {
                    /** @var WP_Error $response */
                    error_log('Failed to check for POWER CAPTCHA plugin updates. Message: '.$response->get_error_message(), E_USER_WARNING);
                    return false;
                } else if( 200 !== wp_remote_retrieve_response_code( $response ) || empty( wp_remote_retrieve_body( $response ) ) ) {
                    error_log('Failed to check for POWER CAPTCHA plugin updates. Response Code: '.wp_remote_retrieve_response_code( $response ), E_USER_WARNING);
                    return false;
                }

                $update_info = json_decode ( wp_remote_retrieve_body( $response ) , True);

                if( !is_array( $update_info ) ) {
                    error_log('Failed to check for POWER CAPTCHA plugin updates. Json could not be decoded.', E_USER_WARNING);
                    return false;
                }

                // cache update info result 
                set_transient( $this->cache_key, $update_info, $this->cache_seconds );

            }

            return $update_info;
        }


        public function info( $res, $action, $args ) {

            // print_r( $action );
            // print_r( $args );

            // do nothing if you're not getting plugin information right now
            if( 'plugin_information' !== $action ) {
                return $res;
            }

            // do nothing if it is not our plugin
            if( $this->plugin_slug !== $args->slug ) {
                return $res;
            }

            // get updates
            $update_info = $this->fetch_update_info();

            if( ! $update_info ) {
                return $res;
            }

            // std class properties: https://rudrastyh.com/wordpress/self-hosted-plugin-update.html#stdclass-props
            $res = new stdClass();

            $res->name = $update_info['name'];
            $res->slug = $update_info['slug'];
            $res->version = $update_info['version'];
            $res->tested = $update_info['tested'];
            $res->requires = $update_info['requires'];
            $res->author = $update_info['author'];
            $res->homepage = $update_info['homepage'];
            // $res->author_profile = $update_info->author_profile;
            $res->download_link = $update_info['download_url'];
            $res->trunk = $update_info['download_url'];
            $res->requires_php = $update_info['requires_php'];
            $res->last_updated = $update_info['last_updated'];

            $res->sections = array(
                'description' => $update_info['sections']['description'],
                'installation' => $update_info['sections']['installation'],
                'changelog' => $update_info['sections']['changelog']
            );

            if( ! empty( $update_info['icons'] ) ) {
                $res->icons = [
                    '1x' => $update_info['icons']['1x'],
                    '2x' => $update_info['icons']['2x']
                ];
            }

            if( ! empty( $update_info['banners'] ) ) {
                $res->banners = [
                    'low' => $update_info['banners']['low'],
                    'high' => $update_info['banners']['high']
                ];
            }

            return $res;

        }

        public function update( $transient ) {

            if ( empty($transient->checked ) ) {
                return $transient;
            }

            $update_info = $this->fetch_update_info();

            if(
                $update_info
                && version_compare( self::CURRENT_VERSION, $update_info['version'], '<' )
                && version_compare( $update_info['requires'], get_bloginfo( 'version' ), '<=' )
                && version_compare( $update_info['requires_php'], PHP_VERSION, '<' )
            ) {
                // new update is available
                $res = new stdClass();
                $res->slug = $this->plugin_slug;
                $res->plugin = $this->plugin;
                $res->new_version = $update_info['version'];
                $res->tested = $update_info['tested'];
                $res->package = $update_info['download_url'];

                $transient->response[ $res->plugin ] = $res;
            }

            return $transient;

        }

        public function purge( $upgrader, $options ){

            if (
                $this->cache_allowed
                && 'update' === $options['action']
                && 'plugin' === $options[ 'type' ]
            ) {
                // just clean the cache when new plugin version is installed
                delete_transient( $this->cache_key );
            }

        }


    }

}