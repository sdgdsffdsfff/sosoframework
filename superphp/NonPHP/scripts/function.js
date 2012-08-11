function RemoteObject(uri){
	this.uri = uri;
}

RemoteObject.prototype.call=function(method,parameterss,callback){
	var pars = 'method='.concat(method,'&','parameters','=',encodeURIComponent(JSON.stringify(parameterss)));
	var myAjax = new Ajax.Request(this.uri,{parameters: pars, onComplete: function(response){callback(JSON.parse(response.responseText));}});
}

RemoteObject.getInstance=function(class_name){
	if(arguments.length==1){
		var path_array=window.location.pathname.split('/');
		path_array.pop();
		path = path_array.join('/');
	}	else {
		path = arguments[1];
	}
	return new RemoteObject(path.concat('/_JSON_', class_name, '.php'));
}

function date_selector(ObjName){
	var return_Value = showModalDialog("/libs/calendar.htm", "yyyy-mm-dd" ,"dialogWidth:286px;dialogHeight:221px;status:no;help:no;");
	if(return_Value){
		ObjName.value = return_Value;
	}
}

function QueryString(sAddress,sName){
	var sSource = String(sAddress);
	var sReturn = "";
	var sQUS = "?";
	var sAMP = "&";
	var sEQ = "=";
	var iPos;
	
	iPos = sSource.indexOf(sQUS);
	
	var strQuery = sSource.substr(iPos, sSource.length - iPos);
	var strLCQuery = strQuery.toLowerCase();
	var strLCName = sName.toLowerCase();
	
	iPos = strLCQuery.indexOf(sQUS + strLCName + sEQ);
	if (iPos == -1){
		iPos = strLCQuery.indexOf(sAMP + strLCName + sEQ);
		if (iPos == -1)
		return "";
	}
	
	sReturn = strQuery.substr(iPos + sName.length + 2,strQuery.length-(iPos + sName.length + 2));
	var iPosAMP = sReturn.indexOf(sAMP);
	
	if (iPosAMP == -1){
		return sReturn;
	}
	else {
		sReturn = sReturn.substr(0, iPosAMP);
	}
	
	return sReturn;
}