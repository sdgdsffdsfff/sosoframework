function select_all()
{
    for(var i=0;i<document.forms('aa').tags('INPUT').length;i++)
    {
        document.forms('aa').tags('INPUT')[i].checked=true;
    }
}

function select_un()
{
    for(var i=0;i<document.forms('aa').tags('INPUT').length;i++)
    {
        document.forms('aa').tags('INPUT')[i].checked=!document.forms('aa').tags('INPUT')[i].checked;
    }
}

function date_selector(ObjName){
	var return_Value = showModalDialog("/libs/calendar.htm", "yyyy-mm-dd" ,"dialogWidth:286px;dialogHeight:221px;status:no;help:no;");
	if(null != return_Value){
	if('string' == typeof(ObjName)){
		ObjName = document.getElementById(ObjName);
	}
        if('object' == typeof(ObjName) && ObjName.tagName == 'INPUT'){
                ObjName.value = return_Value;
        }
}
}

function fclick(obj)
{
  with(obj){
    style.posTop=event.srcElement.offsetTop
    var x=event.x-offsetWidth/2
    if(x<event.srcElement.offsetLeft)x=event.srcElement.offsetLeft
    if(x>event.srcElement.offsetLeft+event.srcElement.offsetWidth-offsetWidth)x=event.srcElement.offsetLeft+event.srcElement.offsetWidth-offsetWidth
    style.posLeft=x
  }
}

function ListView(action,limit){
	if((action != "delete") || confirm('\u786e\u5b9a\u5220\u9664?'))
	{
		var oList = eval("oList_"+limit);
		var oPage = document.getElementById('oPage');
		var page = 1;
		if(oPage) page = oPage.getAttribute('mPage');
		window.location = ('?_action='+action+'&'+oList.getAttribute('prefix_url')+'&_page='+(((page-1)*oPage.getAttribute('mPageSize'))+limit+1));
		return false;
	}
}
