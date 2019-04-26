param( $reposPath, $rev, $txnName )

# Update MantisBT integration with detail of updated log message
# Replace all "<CONFIGURATION_CONSTANTS>" with the appropriate values
$url='https://<YOUR_MANTIS_HOST_HERE>/mantisbt/plugin.php?page=Source/checkin'
$repoName='<MANTISBT_SOURCESVN_REPOSITORY_NAME>'
$apiKey='<MANTISBT_SOURCE_INTEGRATION_APIKEY_VALUE>'

$logFile="$(env:temp)\svn_post-commit_$($repoName)_$($rev).log"

# Assemble HTTP request body
$body= @{
    repo_name=$repoName
    data=$rev
    api_key=$apiKey
}

Invoke-WebRequest -Uri $url -Body $body -Method 'POST' -OutFile $logFile