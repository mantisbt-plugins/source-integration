# Get a login
export WGET_PARAMS="--keep-session-cookies --save-cookies cookies.txt --load-cookies cookies.txt"
export REPO_NAME=test1

set_repo_options()
{
    wget -nv -O- $WGET_PARAMS --post-data="page=Source/repo_update_page&id=`cat repo_id.txt`" http://localhost/mantis/plugin.php | sed -ne 's/.*plugin_Source_repo_update_token" value="\([A-Za-z0-9]*\).*/\1/p' > update_token.txt
    wget -nv -O- $WGET_PARAMS --post-data="page=Source/repo_update&repo_id=`cat repo_id.txt`&plugin_Source_repo_update_token=`cat update_token.txt`&repo_commit_issues_must_exist=$2&repo_commit_needs_issue=$1&repo_name=$REPO_NAME&repo_url=file%3A%2F%2F%2Fhome%2Fvagrant%2Fsvn_repo&svn_username=&svn_password=&standard_repository=&trunk_path=&branch_path=&tag_path=&ignore_paths=" http://localhost/mantis/plugin.php > /dev/null
}

setup_mantis()
{
    wget -nv -O- --keep-session-cookies --save-cookies cookies.txt --post-data 'username=administrator&password=root&perm_login=1' http://localhost/mantis/login.php > /dev/null
    # Install 'Source'
    wget -nv -O- $WGET_PARAMS http://localhost/mantis/manage_plugin_page.php | sed -ne 's/.*\(manage_plugin_install\.php?name=Source[a-zA-Z0-9=&;_]*\).*/\1/p' | sed -e 's/\(&amp;\)/\&/g' > install_url.txt
    wget -nv -O- $WGET_PARAMS http://localhost/mantis/`cat install_url.txt` > /dev/null
    # Install 'Subversion Integration'
    wget -nv -O- $WGET_PARAMS http://localhost/mantis/manage_plugin_page.php | sed -ne 's/.*\(manage_plugin_install\.php?name=SourceSVN[a-zA-Z0-9=&;_]*\).*/\1/p' | sed -e 's/\(&amp;\)/\&/g' > install_url.txt
    wget -nv -O- $WGET_PARAMS http://localhost/mantis/`cat install_url.txt` > /dev/null
}

configure_si()
{
    # Configure Source Integration
    openssl rand -hex 12 > api_key.txt
    wget -nv -O- $WGET_PARAMS http://localhost/mantis/plugin.php?page=Source/manage_config_page | sed -ne 's/.*plugin_Source_manage_config_token" value="\([A-Za-z0-9]*\).*/\1/p' > config_token.txt
    wget -nv -O- $WGET_PARAMS --post-data="enable_message=1&import_urls=localhost&checkin_urls=localhost&bugfix_handler=1&bugfix_message_view_status=10&bugfix_message=Fix%20committed%20to%20\$1%20branch.&bugfix_resolution=20&bugfix_status=-1&bugfix_regex_2=/#?(\\d%2b)/&bugfix_regex_1=/(?:fixe?d?s?|resolved?s?)%2b\\s*:?\\s%2b(?:#(?:\\d%2b)[,\\.\\s]*)%2b/i&buglink_regex_2=/#?(\\d%2b)/&buglink_regex_1=/(?:bugs?|issues?|reports?)%2b\\s*:?\\s%2b(?:#(?:\\d%2b)[,\\.\\s]*)%2b/i&api_key=`cat api_key.txt`&show_repo_link=1&username_threshold=55&manage_threshold=90&update_threshold=20&view_threshold=10&plugin_Source_manage_config_token=`cat config_token.txt`" http://localhost/mantis/plugin.php?page=Source/manage_config > /dev/null
}

setup_mantis_repo()
{
# Set up a repo
wget -nv -O- $WGET_PARAMS http://localhost/mantis/plugin.php?page=Source/index | sed -ne 's/.*plugin_Source_repo_create_token" value="\([A-Za-z0-9]*\).*/\1/p' > create_token.txt
wget -nv -O- $WGET_PARAMS --post-data="repo_name=$REPO_NAME&repo_type=svn&plugin_Source_repo_create_token=`cat create_token.txt`" http://localhost/mantis/plugin.php?page=Source/repo_create > /dev/null
wget -nv -O- $WGET_PARAMS http://localhost/mantis/plugin.php?page=Source/index | grep -A6 $REPO_NAME | sed -ne 's/.*id=\([^"]*\).*/\1/p' > repo_id.txt
}   

delete_mantis_repo()
{
# Set up a repo
wget -nv -O- $WGET_PARAMS --post-data="id=`cat repo_id.txt`" http://localhost/mantis/plugin.php?page=Source/repo_manage_page | sed -ne 's/.*plugin_Source_repo_delete_token" value="\([A-Za-z0-9]*\).*/\1/p' > delete_token.txt
wget -nv -O- $WGET_PARAMS --post-data="_confirmed=1&id=`cat repo_id.txt`&plugin_Source_repo_delete_token=`cat delete_token.txt`" http://localhost/mantis/plugin.php?page=Source/repo_delete > /dev/null
#rm repo_id.txt
}   

setup_project()
{
# Set up a project
wget -nv -O- $WGET_PARAMS http://localhost/mantis/manage_proj_create_page.php | sed -ne 's/.*manage_proj_create_token" value="\([A-Za-z0-9]*\).*/\1/p' > proj_create_token.txt
wget -nv -O- $WGET_PARAMS --post-data="description=test&name=test_project&status=10&view_state=10&repo_type=svn&manage_proj_create_token=`cat proj_create_token.txt`" http://localhost/mantis/manage_proj_create.php > /dev/null
wget -nv -O- $WGET_PARAMS http://localhost/mantis/manage_proj_page.php | grep test_project | sed -ne 's/.*project_id=\([^"]\).*/\1/p' > project_id.txt
# Add a category to the project
wget -nv -O- $WGET_PARAMS http://localhost/mantis/manage_proj_edit_page.php?project_id=`cat project_id.txt` | sed -ne 's/.*manage_proj_cat_add_token" value="\([A-Za-z0-9]*\).*/\1/p' > cat_add_token.txt
wget -nv -O- $WGET_PARAMS --post-data="manage_proj_cat_add_token=`cat cat_add_token.txt`&project_id=`cat project_id.txt`&name=Cat1" http://localhost/mantis/manage_proj_cat_add.php > /dev/null
}

check_test_result()
{
    cmp $1 /vagrant/expectedoutput/$1 > /dev/null 2>&1
    if [ $? == 0 ]
    then
        echo "Test $1: OK"
    else
        echo "Test $1: Failed"
    fi
}

setup_mantis
configure_si
setup_mantis_repo
setup_project

rm -rf test_*.txt
rm -rf svn_repo
mkdir svn_repo
svnadmin create svn_repo
cat /var/www/html/mantis/plugins/SourceSVN/pre-commit.tmpl.mantis-checks-commit | sed -e "s/PROJECT=\"\([a-zA-Z0-9]*\)\"/PROJECT=\"$REPO_NAME\"/;s/API_KEY=.*/API_KEY=\"`cat api_key.txt`\"/" > svn_repo/hooks/pre-commit
cat /var/www/html/mantis/plugins/SourceSVN/post-commit.tmpl | sed -e "s/mantisbt/mantis/;s/PROJECT=\"\([a-zA-Z0-9 ]*\)\"/PROJECT=\"$REPO_NAME\"/;s/API_KEY=.*/API_KEY=\"`cat api_key.txt`\"/" > svn_repo/hooks/post-commit
chmod +x svn_repo/hooks/pre-commit svn_repo/hooks/post-commit

rm -rf svn_sandbox
svn checkout file:///`pwd`/svn_repo svn_sandbox

# Check that we can do a commit without any bug reference when the option is
#  disabled
set_repo_options 0 0
touch svn_sandbox/file1
svn add svn_sandbox/file1 > /dev/null
svn commit -m "Hello" svn_sandbox/file1 > test_not_enabled.txt 2>&1

# Enable 'commit requires issue reference' and try and commit without
#  referencing an issue
set_repo_options 1 0
touch svn_sandbox/file2
svn add svn_sandbox/file2 > /dev/null
svn commit -m "Hello" svn_sandbox/file2 > test_enabled_no_bug_ref.txt 2>&1

# Include a bug reference (it's invalid, but checking of validity is not
#  enabled, so the commit should be accepted
svn commit -m "bug: #9999" svn_sandbox/file2 > test_enabled_invalid_bug_ref_allowed.txt 2>&1

# Turn on checking of bug reference validity & try and commit again -
#  should be rejected
set_repo_options 1 1
touch svn_sandbox/file3
svn add svn_sandbox/file3 > /dev/null
svn commit -m "bug: #9999" svn_sandbox/file3 > test_enabled_invalid_bug_ref_not_allowed.txt 2>&1

delete_mantis_repo

for X in test_*.txt; do
    check_test_result $X;
done

