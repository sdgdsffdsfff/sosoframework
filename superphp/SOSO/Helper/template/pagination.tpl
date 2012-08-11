{literal}
<script language="javascript">
	
	function pagination(){
		this.props = ["mPage","mNextPage","prefix_url","mPageCount","mFirstPage","mPreviousPage","mPageSize","mLastPage","mRecordCount","mStartRecord","mEndRecord"];
		//this['prefix_url'] = '?';
		this['prefix_path'] = '';
		this['hasError'] = false;
		this.initialized = false;
	}
	
	pagination.prototype = {
		constructor : pagination,
		
		init : function(ele,to){
			if(!ele){
				this.hasError = true;
				return;
			}
			if (this.initialized){
				return true;
			}
			for(var p in this.props){
				this[this.props[p]] = ele.getAttribute(this.props[p]);
			}
			this.initialized = true;
		},
		
		render : function(to){
			if(!to) return;
			if(Object.prototype.toString.call(to).match(/Array/) && to.length > 0){
				for(var i=0,len=to.length;i<len;i++) this.render(document.getElementById(to[i]));	
				return;
			}
			if('v' == '\v'){
				to.style.behavior="url(/libs/scripts/htc/page_guide_bar.htc);"
				return;
			}
			this.init(to);
			var oImagePath = "/libs/images/list/";
			if(this.mPage == this.mFirstPage){
				oFirstPage = "<img height=\"15\" alt=\"\" src=\""+oImagePath+"btnFirst2.gif\" width=\"15\" border=\"0\">";
				oPreviousPage = "<img height=\"15\" alt=\"\" src=\""+oImagePath+"btnPrev2.gif\" width=\"15\" border=\"0\">";
			}else{
				oFirstPage = "<a title=\"1\" href=\""+this.prefix_url+"&_page="+this.mFirstPage+"\"> <img height=\"15\" alt=\"\" src=\""+oImagePath+"btnFirst1.gif\" width=\"15\" border=\"0\"></a>";
				oPreviousPage = "<a title=\"2\" href=\""+this.prefix_url+"&_page="+this.mPreviousPage+"\"> <img height=\"15\" alt=\"\" src=\""+oImagePath+"btnPrev1.gif\" width=\"15\" border=\"0\"></a>";
			}
			if(this.mPage==this.mPageCount){
				oPageCount = "<img id=\"btn\" height=\"15\" alt=\"\" src=\""+oImagePath+"btnLast2.gif\" width=\"15\" border=\"0\">";
				oNextPage = "<img id=\"btn\" height=\"15\" alt=\"\" src=\""+oImagePath+"btnNext2.gif\" width=\"15\" border=\"0\">";
			}else{
				oPageCount = "<a title=\"3\" href=\""+this.prefix_url+"&_page="+this.mPageCount+"\"> <img id=\"btn\" height=\"15\" alt=\"\" src=\""+oImagePath+"btnLast1.gif\" width=\"15\" border=\"0\"></a>";
				oNextPage = "<a title=\"4\" href=\""+this.prefix_url+"&_page="+this.mNextPage+"\"> <img id=\"btn\" height=\"15\" alt=\"\" src=\""+oImagePath+"btnNext1.gif\" width=\"15\" border=\"0\"></a>";
			}
			var html = '<table class="lch" cellSpacing="0" cellPadding="0" id="table11"><tr><td vAlign="top">';
				html += '<img height="17" alt="" src="'+oImagePath+'l.gif" width="10" border="0"></td><td width="100%"> ';
				html += '<table cellSpacing="3" cellPadding="0" id="table12"><tr><td class="c" noWrap>&#31532;'+this.mPage+'/'+this.mPageCount;
				html += '&#39029;</td><td><img width=6 height="15" border="0" alt="" src="'+oImagePath+'bullet.gif"></td><td class="c" noWrap>';
				html += '&#20849;&#26377;'+this.mRecordCount+'&#26465;&#35760;&#24405;/&#31532;'+this.mStartRecord+'-'+this.mEndRecord;
				html += '&#26465;&#35760;&#24405;</td><td width="100%" align="center"></td><td> </td><td> <a title="Refresh" href="'+this.prefix_url;
				html += '&_page='+this.mPage+'"> <img id="btn" height="15" alt="" src="'+oImagePath+'btnRefresh1.gif" width="15" border="0"></a>';
				html += '</td><td> '+oFirstPage+'</td><td> '+oPreviousPage+'</td><td> '+oNextPage+'</td><td> '+oPageCount;
				html += '</td></tr></table></td><td vAlign="top"> <img height="17" alt="" src="'+oImagePath+'r.gif" width="10" border="0">';
				html += '</td></tr></table>';
			if(to.tagName.toLowerCase() == 'td'){
				var span = document.createElement('span');
				span.innerHTML = html;
				to.appendChild(span);
			}else{
				to.innerHTML = html;
			}
		}
	};

	var obj = new pagination();
	obj.render(typeof oPages != 'undefined' ? oPages : document.getElementById('oPage'));
{/literal}	
</script>