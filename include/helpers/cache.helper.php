<?php   if(!defined('DEDEINC')) exit("Request Error!");

if ( ! function_exists('GetCache'))
{
    function GetCache($prefix, $key, $is_memcache = TRUE)
    {
        global $cache_helper_config;
        $key = md5 ( $key );

        if ($is_memcache === TRUE && ! empty ( $cache_helper_config['memcache'] ) && $cache_helper_config['memcache'] ['is_mc_enable'] === 'Y')
        {
            $mc_path = empty ( $cache_helper_config['memcache'] ['mc'] [substr ( $key, 0, 1 )] ) ? $cache_helper_config['memcache'] ['mc'] ['default'] : $cache_helper_config['memcache'] ['mc'] [substr ( $key, 0, 1 )];
            $mc_path = parse_url ( $mc_path );
            $key = ltrim ( $mc_path ['path'], '/' ) . '_' . $prefix . '_' . $key;
            if (empty ( $GLOBALS ['mc_' . $mc_path ['host']] ))
            {
                $GLOBALS ['mc_' . $mc_path ['host']] = new Memcache ( );
                $GLOBALS ['mc_' . $mc_path ['host']]->connect ( $mc_path ['host'], $mc_path ['port'] );
            }
            return $GLOBALS ['mc_' . $mc_path ['host']]->get ( $key );
        }
        $key = substr ( $key, 0, 2 ) . '/' . substr ( $key, 2, 2 ) . '/' . substr ( $key, 4, 2 ) . '/' . $key;
        $result = @file_get_contents ( DEDEDATA . "/cache/$prefix/$key.php" );
        
        if ($result === false)
        {
            return false;
        }
        $result = str_replace("<?php exit('dedecms');?>\n\r", "", $result);
        $result = @unserialize ( $result );
        if($result ['timeout'] != 0 && $result ['timeout'] < time ())
        {
              return false;
        }
        return $result ['data'];
    }
}
if ( ! function_exists('SetCache'))
{
    function SetCache($prefix, $key, $value, $timeout = 3600, $is_memcache = TRUE)
    {
        global $cache_helper_config;
        $key = md5 ( $key );

        if (! empty ( $cache_helper_config['memcache'] ) && $cache_helper_config['memcache'] ['is_mc_enable'] === 'Y' && $is_memcache === TRUE)
        {
            $mc_path = empty ( $cache_helper_config['memcache'] ['mc'] [substr ( $key, 0, 1 )] ) ? $cache_helper_config['memcache'] ['mc'] ['default'] : $cache_helper_config['memcache'] ['mc'] [substr ( $key, 0, 1 )];
            $mc_path = parse_url ( $mc_path );
            $key = ltrim ( $mc_path ['path'], '/' ) . '_' . $prefix . '_' . $key;
            if (empty ( $GLOBALS ['mc_' . $mc_path ['host']] ))
            {
                $GLOBALS ['mc_' . $mc_path ['host']] = new Memcache ( );
                $GLOBALS ['mc_' . $mc_path ['host']]->connect ( $mc_path ['host'], $mc_path ['port'] );
            }
            $result = $GLOBALS ['mc_' . $mc_path ['host']]->set ( $key, $value, MEMCACHE_COMPRESSED, $timeout );
            return $result;
        }
        $key = substr ( $key, 0, 2 ) . '/' . substr ( $key, 2, 2 ) . '/' . substr ( $key, 4, 2 ) . '/' . $key;
        $tmp ['data'] = $value;
        $tmp ['timeout'] = $timeout != 0 ? time () + ( int ) $timeout : 0;
        $cache_data = "<?php exit('dedecms');?>\n\r".@serialize ( $tmp );
        return @PutFile ( DEDEDATA . "/cache/$prefix/$key.php",  $cache_data);
    }
}

if ( ! function_exists('DelCache'))
{

    function DelCache($prefix, $key, $is_memcache = TRUE)
    {
        global $cache_helper_config;
        $key = md5 ( $key );

        if (! empty ( $cache_helper_config['memcache'] ) && $cache_helper_config['memcache'] ['is_mc_enable'] === TRUE && $is_memcache === TRUE)
        {
            $mc_path = empty ( $cache_helper_config['memcache'] ['mc'] [substr ( $key, 0, 1 )] ) ? $cache_helper_config['memcache'] ['mc'] ['default'] : $cache_helper_config['memcache'] ['mc'] [substr ( $key, 0, 1 )];
            $mc_path = parse_url ( $mc_path );
            $key = ltrim ( $mc_path ['path'], '/' ) . '_' . $prefix . '_' . $key;
            if (empty ( $GLOBALS ['mc_' . $mc_path ['host']] ))
            {
                $GLOBALS ['mc_' . $mc_path ['host']] = new Memcache ( );
                $GLOBALS ['mc_' . $mc_path ['host']]->connect ( $mc_path ['host'], $mc_path ['port'] );
            }
            return $GLOBALS ['mc_' . $mc_path ['host']]->delete ( $key );
        }
        $key = substr ( $key, 0, 2 ) . '/' . substr ( $key, 2, 2 ) . '/' . substr ( $key, 4, 2 ) . '/' . $key;
        return @unlink ( DEDEDATA . "/cache/$prefix/$key.php" );
    }
}