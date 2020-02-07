param( $reposPath, $rev, $user, $propName, $action )

# Replace all "<CONFIGURATION_CONSTANTS>" with the appropriate values
$url='https://<YOUR_MANTIS_HOST_HERE>/mantisbt/plugin.php?page=Source/checkin'
$repoName='<MANTISBT_SOURCESVN_REPOSITORY_NAME>'
$apiKey='<MANTISBT_SOURCE_INTEGRATION_APIKEY_VALUE>'

if ( $propName -eq 'svn:log' )
{
    $logFile="$(env:temp)\svn_post-revprop-change_$($repoName)_$($rev).log"

    # Assemble HTTP request body
    $body = @{
        repo_name=$repoName
        data=$rev
        revprop='TRUE'
        api_key=$apiKey
    }

    # Update MantisBT integration with detail of updated log message
    Invoke-WebRequest -Uri $url -Body $body -Method 'POST' -OutFile $logFile
}