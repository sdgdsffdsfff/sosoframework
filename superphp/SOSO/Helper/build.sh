#!/bin/bash
# build.sh - Provide project-building services.
# version $Id:1 2009-11-23 15:20:46Z moonzhang $ 
 
PROG=$(dirname "$0")"/"$(basename "$0")
DIRTREE="mkdir -p code/{scripts,css,images,WEB-INF/{template/{user,admin,tableadmin},temp/{cache,template_c,config},class/{Page/{User,Admin},Cron}}} resources"
PACKAGE=$(cd "$(dirname $0)";pwd)

while [ ! $(basename "$PACKAGE") = 'SOSO' ] 
    do PACKAGE=$(dirname "$PACKAGE")
    done
PACKAGE=$(dirname "$PACKAGE")
LIBPATH="$PACKAGE"
PACKAGE=$PACKAGE"/resources/Samples/Project structure.tar"
zipFile=$(basename "$PACKAGE" ".tar")


msg(){
    echo "$@" 1>&2
}

usage(){
    cat <<EOF
    $0 [project_name] [project_path]
EOF
    exit 2
}
prompt_project(){
    echo -e "Please input project name:"
    read proj_name
    if [ -z "$proj_name" ];then
        msg "Project Name must be specified "; 
        prompt_project
        exit 1
    fi
    echo 
    echo -e "Please specify project directory path:"
    read proj_dir
    if [ ! -d "$proj_dir" ];then
        msg "Project path must be specified and it's must be exist";
        prompt_project
        exit 2
    fi
    proj_dir=$(cd $proj_dir;pwd)
    cd $proj_dir
}

is_fail(){
 #   if [ $? -ne 0 ] ; then
        msg ${1:-"failed"}
        exit ${2:-5}
#    fi
}

#initialize web.xml and entry.php
do_init(){
    cd "$proj_name/code/WEB-INF/" || is_fail "failed to cd $proj_name/code/WEB-INF/"
    if [ -f 'entry.php-dist' ];then 
        cp 'entry.php-dist' 'entry.php' || is_fail "failed to copy entry.php-dist"
        if [ "$(uname -s)" = 'Linux' ];then
            sed -i -e '2,3d' -e "s|#PROJECTNAME#|${proj_name}|g" -e "s|#LIBPATH#|${LIBPATH}|" entry.php || is_fail "failed to modify entry.php file!You'd modify it manually"
        elif [ "$(uname -s)" = 'Darwin' ];then
            sed -e '2,3d' -e "s|#PROJECTNAME#|${proj_name}|g" -e "s|#LIBPATH#|${LIBPATH}|" entry.php > entry2.php
            mv entry2.php entry.php
        fi
    fi

    if [ -f 'web.xml-dist' ];then
        cp 'web.xml-dist' 'web.xml' 2>/dev/null || is_fail "failed to copy web.xml-dist"
    #    echo "Will you config web.xml?[y/n]"
    #    read is_config_xml
    #    if [ grep -c -E "[Yy]" "$is_config_xml" -eq 1 ];then
            
        
    #    fi
    fi

}

if [ $# -eq 0 ];then
    prompt_project
elif [ $# -eq 1 -a $(echo "$1" |grep -c -E 'help$') -eq 1 ];then
    usage    
elif [ $# -eq 2 ];then
    if [ ! -d "$2" ];then
        msg "$2 is not a valid directory path"
    fi

    if [ -d "$2/$1" ];then
        msg "there is already '$2/$1'!Please specify a new one!"
    fi
    proj_name="$1"
    proj_dir=$(cd "$2";pwd)

fi

if [ ! -f "$PACKAGE" ];then
    mkdir -p "$proj_name"
    $(cd "$proj_name" ;eval $DIRTREE) || is_fail "Create Project-Path-Struct failed!"
else
    cmd="tar -xf '$PACKAGE' -C '$proj_dir';mv '$zipFile' '$proj_name'"
    $(eval $cmd) || is_fail "Create Project-Path-Struct failed!"
fi
   echo struct builded
   path1="$proj_name/code/WEB-INF/temp"
   chmod -R 0777 "$path1" "${path1}late"
   path2=$(dirname "$PACKAGE")"/Blank/WEB-INF"
   find "$path2" -name "*dist" -type f -exec cp {} "$proj_name/code/WEB-INF/" \;

do_init
exit 0
