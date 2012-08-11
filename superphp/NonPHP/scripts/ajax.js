function ajaxRequest(url, callback, data, charset){
	if(typeof data == 'undefined')
		data = null;
	if(typeof charset == 'undefined')
		charset = 'UTF-8';

	var request = null;
	if(window.XMLHttpRequest){
		request = new XMLHttpRequest();
	}
	else{
		var MSXMLs = ['MSXML2.XMLHTTP.6.0', 'MSXML2.XMLHTTP.3.0', 'MSXML2.XMLHTTP.5.0', 'MSXML2.XMLHTTP.4.0', 'MSXML2.XMLHTTP', 'Microsoft.XMLHTTP'];
		for(var n = 0; n < MSXMLs.length; n ++){
			try{
				request = new ActiveXObject(MSXMLs[n]);
				break;
			} catch(e){};	
		}
	}
	if(request != null){
		request.onreadystatechange = function(){
			if (request.readyState == 4 && request.responseText){
				callback(request.responseText);
				request = null;
			}
		};
		var method = data == null ? 'GET' : 'POST';
		request.open(method, url, true);
		if(method == 'POST'){
			request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=' + charset);
		}
		else{
			if(charset != 'UTF-8')
				request.setRequestHeader('charset', charset);
		}
        request.send(data);
	}
}