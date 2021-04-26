function Music() {
    this.init();
}
(function () {
    var pages = [],
        panels = [],
        selectedItem = null;
    Music.prototype = {
        total:70,
        pageSize:10,
        dataUrl:"http://s.music.163.com/search/get/?type=1&s=",
        playerUrl:"http://music.163.com/song/media/outer/url?id=",

        init:function () {
            var me = this;
            switchTab("musicTab");
            domUtils.on($G("J_searchName"), "keyup", function (event) {
                var e = window.event || event;
                if (e.keyCode == 13) {
                    me.dosearch();
                }
            });
            domUtils.on($G("J_searchBtn"), "click", function () {
                me.dosearch();
            });
        },
        callback:function (data) {
            var me = this;
            me.data = data.result.songs;
            setTimeout(function () {
                $G('J_resultBar').innerHTML = me._renderTemplate(data.result.songs);
            }, 300);
        },
        dosearch:function () {
            var me = this;
            selectedItem = null;
            var key = $G('J_searchName').value;
            if (utils.trim(key) == "")return false;
            key = encodeURIComponent(key);
            me._sent(key);
        },
        doselect:function (i) {
            var me = this;
            if (typeof i == 'object') {
                selectedItem = i;
            } else if (typeof i == 'number') {
                selectedItem = me.data[i];
            }
        },
        onpageclick:function (id) {
            var me = this;
            for (var i = 0; i < pages.length; i++) {
                $G(pages[i]).className = 'pageoff';
                $G(panels[i]).className = 'paneloff';
            }
            $G('page' + id).className = 'pageon';
            $G('panel' + id).className = 'panelon';
        },
        listenTest:function (elem) {
            var me = this,
                view = $G('J_preview'),
                is_play_action = (elem.className == 'm-try'),
                old_trying = me._getTryingElem();

            if (old_trying) {
                old_trying.className = 'm-try';
                view.innerHTML = '';
            }
            if (is_play_action) {
                elem.className = 'm-trying';
                view.innerHTML = me._buildMusicHtml(me._getUrl(true));
            }
        },
        _sent:function (param) {
            var me = this;
            $G('J_resultBar').innerHTML = '<div class="loading"></div>';

            utils.loadFile(document, {
                src:me.dataUrl + param + '&limit=' + me.total + '&callback=music.callback',
                tag:"script",
                type:"text/javascript",
                defer:"defer"
            });
        },
        _removeHtml:function (str) {
            var reg = /<\s*\/?\s*[^>]*\s*>/gi;
            return str.replace(reg, "");
        },
        _getUrl:function (isTryListen) {
            var me = this;
			//https://music.163.com/song/media/outer/url?id=33894312.mp3
            return  me.playerUrl + selectedItem.id + ".mp3";
        },
        _getTryingElem:function () {
            var s = $G('J_listPanel').getElementsByTagName('span');

            for (var i = 0; i < s.length; i++) {
                if (s[i].className == 'm-trying')
                    return s[i];
            }
            return null;
        },
        _buildMusicHtml:function (playerUrl) {
            var html = '<audio src="'+playerUrl+'" controls="controls" autoplay="autoplay"></audio>';
            return html;
        },
        _byteLength:function (str) {
            return str.replace(/[^\u0000-\u007f]/g, "\u0061\u0061").length;
        },
        _getMaxText:function (s) {
            var me = this;
            s = me._removeHtml(s);
            if (me._byteLength(s) > 12)
                return s.substring(0, 5) + '...';
            if (!s) s = "&nbsp;";
            return s;
        },
        _rebuildData:function (data) {
            var me = this,
                newData = [],
                d = me.pageSize,
                itembox;
            for (var i = 0; i < data.length; i++) {
                if ((i + d) % d == 0) {
                    itembox = [];
                    newData.push(itembox)
                }
                itembox.push(data[i]);
            }
            return newData;
        },
        _renderTemplate:function (data) {
            var me = this;
            if (data.length == 0)return '<div class="empty">' + lang.emptyTxt + '</div>';
            data = me._rebuildData(data);
            var s = [], p = [], t = [];
            s.push('<div id="J_listPanel" class="listPanel">');
            p.push('<div class="page">');
            for (var i = 0, tmpList; tmpList = data[i++];) {
                panels.push('panel' + i);
                pages.push('page' + i);
                if (i == 1) {
                    s.push('<div id="panel' + i + '" class="panelon">');
                    if (data.length != 1) {
                        t.push('<div id="page' + i + '" onclick="music.onpageclick(' + i + ')" class="pageon">' + (i ) + '</div>');
                    }
                } else {
                    s.push('<div id="panel' + i + '" class="paneloff">');
                    t.push('<div id="page' + i + '" onclick="music.onpageclick(' + i + ')" class="pageoff">' + (i ) + '</div>');
                }
                s.push('<div class="m-box">');
                s.push('<div class="m-h"><span class="m-t">' + lang.chapter + '</span><span class="m-s">' + lang.singer
                    + '</span><span class="m-z">' + lang.special + '</span><span class="m-try-t">' + lang.listenTest + '</span></div>');
                for (var j = 0, tmpObj; tmpObj = tmpList[j++];) {
                    s.push('<label for="radio-' + i + '-' + j + '" class="m-m">');
                    s.push('<input type="radio" id="radio-' + i + '-' + j + '" name="musicId" class="m-l" onclick="music.doselect(' + (me.pageSize * (i-1) + (j-1)) + ')"/>');
                    s.push('<span class="m-t">' + me._getMaxText(tmpObj.name) + '</span>');
                    s.push('<span class="m-s">' + me._getMaxText(tmpObj.artists[0].name) + '</span>');
                    s.push('<span class="m-z">' + me._getMaxText(tmpObj.album.name) + '</span>');
                    s.push('<span class="m-try" onclick="music.doselect(' + (me.pageSize * (i-1) + (j-1)) + ');music.listenTest(this)"></span>');
                    s.push('</label>');
                }
                s.push('</div>');
                s.push('</div>');
            }
            t.reverse();
            p.push(t.join(''));
            s.push('</div>');
            p.push('</div>');
            return s.join('') + p.join('');
        },
        exec:function () {
            var me = this;
			var o=$G("local_").className=="focus";
			var url2param={
				url:$G("songurl").value,
				title:$G("songname").value,
				author:$G("authorname").value,
				album_title:$G("songalbum").value
			};
            if (!o && selectedItem == null)   return;
            $G('J_preview').innerHTML = "";
			$G("J_preview2").innerHTML ="";
            editor.execCommand('music', {
				url:(o ? url2param.url : me._getUrl(false)),
                width:400,
                height:55
            });
        }
    };
})();
function switchTab( tabParentId,keepFocus ) {
	var tabElements = $G( tabParentId ).children,
		tabHeads = tabElements[0].children,
		tabBodys = tabElements[1].children;
	for( var i = 0, length = tabHeads.length; i < length; i++ ){
		var head = tabHeads[i];
		domUtils.on( head, "click", function(){
			for( var k = 0, len = tabHeads.length; k < len; k++ ){
				if(!keepFocus)tabHeads[k].className = "";
			}
			this.className = "focus";
			var tabSrc = this.getAttribute( "tabSrc" );
			for( var j = 0, length = tabBodys.length; j < length; j++ ){
				var body = tabBodys[j],
					id = body.getAttribute( "id" );
				if( id == tabSrc ){
					body.style.display = "";
				}else{
					body.style.display = "none";
				}
			}
		});
	}
}