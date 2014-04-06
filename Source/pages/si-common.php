<?php

function si_is_key_ok()
{
    return ( gpc_get_string( 'api_key' ) == plugin_config_get( 'api_key' ) && trim(plugin_config_get( 'api_key' )) != '');
}

?>
