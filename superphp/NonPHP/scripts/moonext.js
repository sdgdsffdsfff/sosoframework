
/**
 * 基于extjs的封装,多应用于后台管理页面中
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.0.1 2008-08-21
 */
if ((typeof Ext == 'undefined')) 
    throw ("MoonExt requires the Extjs JavaScript framework!");

var MoonExt = {
    Version: '1.0.1',
    ExtVersion: parseFloat(Ext.version.split(".")[0] + "." + Ext.version.split(".")[1]),
    require: function(libraryName){
        document.write('<scr' + 'ipt type="text/javascript" src="' + libraryName + '"></scr' + 'ipt>');
    },
    load: function(file){
        if (this.ExtVersion < 2.0) 
            throw ("MoonExt requires the Ext JavaScript framework >= 2.0");
        if (typeof file == 'undefined') 
            return false;
        if (file.constructor == Array) {
            for (var i = 0, len = file.length; i < len; i++) {
                this.include(file[i]);
            }
        }
    },
    include: function(f, cb){
        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement('script');
        script.type = "text/javascript";
        script.src = f;
        head.appendChild(script);
        if (typeof cb == 'function') {
        
        }
    },
    fullscreen: function(){
        var w = 0;
        if (Ext.isStrict) {
            w = document.documentElement.clientWidth;
        }
        else {
            w = document.body.clientWidth;
        }
        if (w < window.screen.width) {
            window.moveTo(0, 0);
            window.resizeTo(window.screen.width, window.screen.height);
        }
    },
    xformat: function(format, data){
        return format.replace(/\{(.+?)\}/g, function(m, i){
            return data[i];
        });
    }
};
//MoonExt.load(['/libs/scripts/ext-all.js','/libs/scripts/adapter/ext/ext-base.js']);

Ext.QuickTips.init();
MoonExt.Widgets = function(){
}

Ext.override(MoonExt.Widgets, {
    createEditor: function(config){
		var c = {id:'moonEditor',el:Ext.getBody(),width:'100%'};
		Ext.apply(c,config);
        var obj = Ext.getCmp(c.id);
        if (!obj) {
            obj = new Ext.form.HtmlEditor({
                id: c.id
            });
            obj.render(c.el);
        }
		if (c.val){
			obj.setValue(c.val);	
		}
		return obj;
    },
    //日历控件
    createDate: function(title, label, name, w, format, ele, defaultValue){
        var obj = new Ext.form.DateField({
            title: title,
            fieldLabel: label || '日期',
            format: format || "Y-m-d",
            editable: true,
            width: w || 180,
            allowBlank: true,
            name: name,
            // msgTarget:'side',
            //readOnly: true,
            id: name
        });
        if (Ext.getDom(ele)) {
            obj.render(ele);
        }
        if (defaultValue) {
            obj.setValue(defaultValue);
        }
        return obj;
    },
    resizeEle: function(id, config, callback){
        if (!Ext.getDom(id)) 
            return false;
        var normal = {
            width: 450,
            height:200,
            minWidth: 400,
            minHeight: 50,
            maxWidth: 700,
            dynamic: true,
            wrap: true,
            handles: 'e',
            easing: 'backIn',
            duration: .6
        };
        if (config) 
            Ext.apply(normal, config);
        var ret = new Ext.Resizable(id, normal);
        if (typeof callback == 'function') {
            ret.on('resize', callback);
        }
    },
    markInvalid: function(ele, msg){
        if (ele.constructor == Array && ele.length > 0) {
            var x;
            while (x = ele.pop()) {
                this.markInvalid(x, msg);
            }
            return true;
        }
        var e = Ext.get(ele);
        if (!e) {
            return false;
        }
        e.removeClass('ok');
        e.addClass('x-form-invalid');
        e.dom.qtip = msg || "出错了!";
        e.dom.qclass = 'x-form-invalid-tip';
        if (Ext.QuickTips) {
            Ext.QuickTips.enable();
        }
    },
    /**
     * 异步提交方法
     * @param {string} url
     * @param {Object} params
     * @param {string} method GET|POST
     * @param {Function} succCallback
     * @param {Function} failCallback
     */
    request: function(url, params, method, succCallback, failCallback){
        Ext.Ajax.request({
            url: url,
            params: params,
            method: method || "GET",
            headers: {
                'X-PoweredBy': 'Moon Zhang'
            },
            success: function(B, C){
                var D = Ext.util.JSON.decode(B.responseText);
                if (typeof succCallback == 'function') {
                    succCallback(D);
                }
            },
            failure: function(B, C){
                if (typeof failCallback == 'function') {
                    failCallback(B, C);
                }
            },
            scope: this
        });
    },
    
    createGrid: function(data, ele, w, h, title, proxy){
        if (!data instanceof Array) {
            Ext.getDom(ele).innerHTML = "数据不是数组!";
            return {};
        }
        var grid;
        grid = new MoonExt.Widgets.Grid(data, ele, w, h, title, proxy);
        //grid.getSelectionModel().selectFirstRow();
        var o = {
            _page: (page.mCurrentPage - 1) * page.mPagesize,
            limit: page.mPagesize
        };
        grid.store.load({
            params: o
        });
        /*if (Ext.isIE) {
            grid.autoHeighter();
        }*/
        grid.autoHeighter();
        return grid;
    }
});

/**
 * @class MoonExt.Widgets.Grid
 * @extends Ext.grid.GridPanel
 *
 * 表格控制类
 */
MoonExt.Widgets.Grid = function(data, ele, w, h, title, proxyfile){
    var config = {};
    var pk = '';
    if (primary_key) {
    		var qsa = location.search.replace(/\?|_action=[^&]*&*/g, '');
        for (var i = 0, len = primary_key.length; i < len; i++) {
            pk += primary_key[i] + "={" + primary_key[i] + "}&";
            var re = new RegExp(primary_key[i]+"=[^&]*&*","g");
            qsa = qsa.replace(re,'');
        }
        _Moon_delete_fn=function(){
        	if (confirm('确定要删除吗')){
        	  return true;	
        	}
        	return false;	
        }
        
        var t = '<a class="icon-editx" title="edit" href="?_action=update&' +qsa + "&" + pk + '">&#32534;&#36753;</a> ' +
        "<a class='icon-deletex' title='delete'  onclick='return _Moon_delete_fn();' href='?_action=delete&" +qsa+ "&" +
        pk +
        "'>&#21024;&#38500;</a> " +
        "<a class='icon-viewx' title='view' href='?_action=select&" +qsa+ "&" +
        pk +
        "'>&#26597;&#30475;</a>";
        Ext.each(data, function(){
            this['操作'] = MoonExt.xformat(t, this);
        });
    }
    
    var myData = {
        gridData: data,
        results: (page && page.mTotalResult) ? page.mTotalResult : 0
    };
    // var sm = new Ext.grid.CheckboxSelectionModel();
    var fileds = this.fetchFields(data[0]);
    var columns = this.fetchColumns(data[0]);
    var proxy = new Ext.data.MemoryProxy(myData);
    var reader = new Ext.data.JsonReader({
        root: 'gridData',
        totalProperty: 'results',
        data: myData,
        fields: fileds
    });
    
    var store = new Ext.data.Store({
        //data: myData,
        proxy: proxy,
        reader: reader,
        remoteSort: false
    
        /*proxy: new Ext.data.HttpProxy({
         url: proxyfile
         })*/
    });
    store.loadData(myData);
    var cm = new Ext.grid.ColumnModel(columns);
    cm.defaultSortable = true;
    
    var pageConfig = {
        //id:'moonext-p',
        pageSize: page.mPagesize,
        store: store,
        displayInfo: true,
        displayMsg: '显示数据  {0} - {1} of {2}',
        emptyMsg: "暂无数据"
    };
    var pagingBar = new Ext.PagingToolbar(Ext.apply(pageConfig), {
        id: 'moonext-p'
    });
    var pagingBar2 = new Ext.PagingToolbar(Ext.apply(pageConfig), {
        id: 'moonext-p2'
    });
    pagingBar2.paramNames.start = pagingBar.paramNames.start = "_page";
    pagingBar.doLoad = pagingBar2.doLoad = function(start){
        var o = {}, pn = this.paramNames;
        o[pn.start] = start;
        o[pn.limit] = this.pageSize;
        this.store.load({
            params: o
        });
        if (!isNaN(start)) {
            var search = "?_page=" + (start / this.pageSize + 1) + "&" + location.search.replace(/\?|_page=\d*&*/g, '');
            location.href = location.pathname + search;
        }
    };
    
    config = {
        store: store,
        loadMask: true,
        renderTo: ele,
        collapsible: true,
        id: 'MoonExt-' + ele,
        //autoHeight:true,
        autoScroll: true,
        cm: cm,
        /*buttons: [{
            text: 'Save'
        }, {
            text: 'Cancel'
        }],
        buttonAlign: 'left',
        */
        //		draggable:true,
        stripeRows: true,
        height: h || Ext.getBody().dom.clientHeight,
        width: w || Ext.getBody().dom.clientWidth,
        title: title || '数据列表',
        iconCls: 'moon-grid',
        sm: new Ext.grid.RowSelectionModel({
            singleSelect: true
        }),
        //sm:sm,
        monitorWindowResize: true,
        trackMouseOver: true,
        resizable: true,
        tools: [{
            id: 'refresh',
            on: {
                click: function(){
                    var g = Ext.ComponentMgr.get('MoonExt-' + ele);
                    g.body.mask('Loading', 'x-mask-loading');
                    setTimeout(function(){
                        g.body.unmask();
                    }, 1000);
                }
            }
        }], //end tools
        viewConfig: {
            forceFit: false,
            resizable: true,
            enableRowBody: true,
            showPreview: false,
            getRowClass: function(record, rowIndex, p, store){
                if (this.showPreview) {
                    p.body = '<p>' + record.data.post_title + '</p>';
                    return 'x-grid3-row-expanded';
                }
                return 'x-grid3-row-collapsed';
            }
        },
        bbar: pagingBar,
        tbar: pagingBar2
    };
    //	alert(config.height+"\n"+config.width);
    MoonExt.Widgets.Grid.superclass.constructor.call(this, config);
}

MoonExt.Widgets.Pagination = {};

Ext.extend(MoonExt.Widgets.Grid, Ext.grid.GridPanel, {
    autoHeighter: function(){
        /*var len = this.store.getCount() + 1;
        if (len > 50) {
            return true;
        }
        var items_1_screen = 24;
        var h = this.el.getHeight();
        var res = page.mPagesize || items_1_screen;
        
        var minus = this.body.getHeight() * (1 - len / items_1_screen);
        this.setHeight(h - minus + 20);*/
        var items_len = this.store.getCount();
        if (items_len >= 50){
        	return true;	
        }
        var item_height = 26;
        items_len += 5;
        this.setHeight(item_height * items_len);
    },
    loadData: function(data){
        if (!data.gridData) {
            data = {
                gridData: data
            };
        }
        this.colModel.setConfig(this.fetchColumns(data['gridData'][0]));
        this.store.loadData(data);
        this.getView().layout();
    },
    //private
    fetchColumns: function(data){
        var ret = [new Ext.grid.RowNumberer()];
        //var ret = [new Ext.grid.CheckboxSelectionModel()];
        for (var p in data) {
            ret.push({
                header: p.charAt(0).toUpperCase() + p.substr(1),
                sortable: true,
                dataIndex: p
            });
        }
        return ret;
    },
    //private
    fetchFields: function(data){
        var ret = [];
        for (var p in data) {
        	ret.push({name:p,type:'string'});
        }
        return ret;
    },
    /**
     * 监听点击行的动作
     *
     * @param {Object} handler
     */
    rowselect: function(handler){
        if (!handler instanceof Function) {
            return false;
        }
        this.getSelectionModel().on('rowselect', function(a, b, c){
            var r = this.store.getAt(arguments[1])['data'];
            var ret = [];
            for (var p in r) {
                ret.push(r[p]);
            }
            handler(ret);
        }, this);
    },
    copyData: function(){
        var n = this.store.getCount();
        var txt = "";
        Ext.each(this.colModel.config, function(a){
            if (!Ext.isEmpty(this.header.trim())) 
                txt += this.header + "\t";
        });
        txt += "\n";
        for (var i = 0; i < n; i++) {
            var r = this.store.getAt(i)['data'];
            for (var p in r) {
                txt += r[p] + "\t";
            }
            txt += "\n";
        }
        
        if (Ext.isIE && !Ext.isIE7 && window.clipboardData) {
            window.clipboardData.setData("Text", txt);
        }
        else {
            var flashcopier = 'flashcopier';
            if (!document.getElementById(flashcopier)) {
                var divholder = document.createElement('div');
                divholder.id = flashcopier;
                document.body.appendChild(divholder);
            }
            var so = new SWFObject('/libs/scripts/_clipboard.swf', 'copy_contents', '0', '0', '4');
            so.addVariable('clipboard', txt/*escape(txt)*/);
            so.write(flashcopier);
        }
        return txt;
    }
});

var widgets = new MoonExt.Widgets();
Ext.BLANK_IMAGE_URL = '/libs/resources/images/default/s.gif';
