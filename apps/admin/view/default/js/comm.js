$(document).ready(function (e) {

    //菜单高亮显示
    light_nav();

    //选择全部
    $("#selectall").on("click", function () {
        $("#selectitem input:checkbox").prop("checked", true);

    })

    //反选
    $("#invselect").on("click", function () {
        $("#selectitem input:checkbox").each(function() {
            if($(this).prop("checked")){
                $(this).prop("checked",false);
            }else{
                $(this).prop("checked",true);
            }
        })
    })

    //勾选方式选择全部
    $("#checkall").on("click", function () {
        if($(this).prop("checked")){
            $(".checkitem:enabled").prop("checked", true);
        }else{
            $(".checkitem").prop("checked", false);
        }

    })

    var i=0;
    $('.menu-ico').click(function(){
        if($(window).width()>750){
            if(i==0){//隐藏
                $(".layui-side").animate({width:'toggle'});
                $(".layui-body").animate({left:'0px'});
                $(".layui-footer").animate({left:'0px'});
                i=1
            }else{//显示
                $(".layui-side").animate({width:'toggle'});
                $(".layui-body").animate({left:'200px'});
                $(".layui-footer").animate({left:'200px'});
                i=0
            }
        }else{
            $(".layui-side").animate({width:'toggle'});
        }
    });


    $(window).resize(function(){
        if($(window).width()>750){ //大屏幕根据情况判断
            if(i==0){ //等于0，说明处于显示状态，全屏以后保持显示出来
                $(".layui-layout-admin .layui-side").show();
            }else{ //等于1，说明处于隐藏状态，全屏以后保持隐藏出来
                $(".layui-layout-admin .layui-side").hide();
            }
        }

        if($(window).width()<750){//小屏幕，直接隐藏
            $(".layui-layout-admin .layui-side").hide();
        }
    })

    //避免tab翻页问题
    var hash = location.hash;
    if(hash){
        $('.page').find('a').each(function(index,element){
            $(this).attr('href', $(this).attr('href')+hash);
        });
    }


	function ekoText(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function(s) {
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#39;'
			}[s];
		});
	}

	function ekoAdminUrl(url) {
		if (!url || typeof url !== 'string') {
			return false;
		}
		var link = document.createElement('a');
		link.href = url;
		if (link.protocol !== 'http:' && link.protocol !== 'https:') {
			return false;
		}
		if (link.host !== window.location.host) {
			return false;
		}
		var path = link.pathname || '';
		var search = link.search || '';
		if ((path === '/' || path === '') && !search) {
			return false;
		}
		var curPath = window.location.pathname || '';
		var curScript = curPath.substring(curPath.lastIndexOf('/') + 1).toLowerCase();
		var linkScript = path.substring(path.lastIndexOf('/') + 1).toLowerCase();
		if (/\.php$/i.test(curScript)) {
			if (linkScript !== curScript) {
				return false;
			}
			if (!/[?&]p=\/[A-Za-z0-9]+/i.test(search)) {
				return false;
			}
			return true;
		}
		if (!/\/admin(\/|$)/i.test(curPath) || !/\/admin(\/|$)/i.test(path)) {
			return false;
		}
		return true;
	}

	function ekoUnlock(form, sourceBtn) {
		form.removeAttribute('data-eko-lock');
		var origin = form.getAttribute('data-eko-origin-action');
		if (origin) {
			form.setAttribute('action', origin);
			form.action = origin;
		}
		var nodes = form.querySelectorAll('[data-eko-disabled]');
		var i;
		for (i = 0; i < nodes.length; i++) {
			nodes[i].disabled = false;
			nodes[i].removeAttribute('data-eko-disabled');
		}
		if (sourceBtn) {
			sourceBtn.removeAttribute('data-eko-lock');
			if (sourceBtn.getAttribute('data-eko-disabled') === '1') {
				sourceBtn.disabled = false;
				sourceBtn.removeAttribute('data-eko-disabled');
			}
		}
	}

	function ekoDisposeForm(form) {
		if (form && form.getAttribute('data-eko-ephemeral') === '1' && form.parentNode) {
			form.parentNode.removeChild(form);
		}
	}

	function ekoTip(kind, message, time, done) {
		var show = function(layer) {
			if (kind === 'msg') {
				layer.msg(message, {time: time || 800}, done || function() {});
			} else if (done) {
				layer.alert(message, done);
			} else {
				layer.alert(message);
			}
		};
		if (typeof layer !== 'undefined') {
			show(layer);
		} else if (typeof layui !== 'undefined') {
			layui.use('layer', show);
		} else if (kind === 'msg') {
			if (done) {
				done();
			}
		} else {
			window.alert(message);
			if (done) {
				done();
			}
		}
	}

	// 读取服务端写入的表单提示，在当前页面内联展示
	try {
		var ekoFlash = sessionStorage.getItem('ekoFlash');
		if (ekoFlash) {
			sessionStorage.removeItem('ekoFlash');
			var ekoFlashData = JSON.parse(ekoFlash);
			if (ekoFlashData && ekoFlashData.msg) {
				ekoTip(ekoFlashData.type === 'error' ? 'alert' : 'msg', ekoText(ekoFlashData.msg), ekoFlashData.type === 'error' ? 0 : 1600);
			}
		}
	} catch (ekoFlashError) {}

	window.ekoAjaxSubmit = function(form, options) {
		options = options || {};
		var sourceBtn = options.sourceBtn || null;
		if (!form || form.getAttribute('data-eko-lock') === '1') {
			return;
		}
		if (sourceBtn && sourceBtn.getAttribute('data-eko-lock') === '1') {
			return;
		}
		var button = sourceBtn || form.__ekoSubmitter;
		if (!options.confirmed) {
			var ask = (button && button.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
			if (ask && !window.confirm(ask)) {
				return;
			}
		}
		if (!form.getAttribute('data-eko-origin-action')) {
			form.setAttribute('data-eko-origin-action', form.getAttribute('action') || form.action || '');
		}
		if (typeof normalizeAllEditorsBeforeSubmit === 'function') {
			try {
				normalizeAllEditorsBeforeSubmit();
			} catch (ignore) {}
		}
		form.setAttribute('data-eko-lock', '1');
		if (sourceBtn) {
			sourceBtn.setAttribute('data-eko-lock', '1');
			if (!sourceBtn.disabled) {
				sourceBtn.disabled = true;
				sourceBtn.setAttribute('data-eko-disabled', '1');
			}
		}
		var token = String(new Date().getTime()) + Math.random();
		form.setAttribute('data-eko-token', token);
		var data = $(form).serializeArray();
		var i;
		// 批量推送只提交勾选行的数据，避免整页隐藏域（listall/sorting/urls）撑大请求触发输入上限
		if (button && button.getAttribute('data-selected-only') === '1') {
			var checked = {};
			for (i = 0; i < data.length; i++) {
				if (data[i].name === 'list[]') {
					checked[data[i].value] = true;
				}
			}
			var picked = [];
			for (i = 0; i < data.length; i++) {
				var urlKey = /^urls\[([^\]]+)\]$/.exec(data[i].name);
				if (urlKey) {
					if (checked[urlKey[1]]) {
						picked.push(data[i]);
					}
				} else if (data[i].name !== 'listall[]' && data[i].name !== 'sorting[]') {
					picked.push(data[i]);
				}
			}
			data = picked;
		}
		if (button && button.name && button.className.indexOf('ajax-sorting') === -1) {
			var exists = false;
			for (i = 0; i < data.length; i++) {
				if (data[i].name === button.name) {
					exists = true;
				}
			}
			if (!exists) {
				data.push({
					name: button.name,
					value: button.value || ''
				});
			}
		}
		var nodes = form.querySelectorAll('input, select, textarea, button');
		for (i = 0; i < nodes.length; i++) {
			if (!nodes[i].disabled) {
				nodes[i].disabled = true;
				nodes[i].setAttribute('data-eko-disabled', '1');
			}
		}
		var after = (button && button.getAttribute('data-after')) || form.getAttribute('data-after') || 'reload';
		var postUrl = (button && button.getAttribute('data-action')) || form.action;
		var ajaxTimeout = parseInt((button && button.getAttribute('data-timeout')) || form.getAttribute('data-timeout'), 10);
		if (!ajaxTimeout || ajaxTimeout <= 0) {
			ajaxTimeout = 30000;
		}
		$.ajax({
			type: 'POST',
			url: postUrl,
			data: data,
			dataType: 'json',
			timeout: ajaxTimeout,
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			success: function(response) {
				if (form.getAttribute('data-eko-token') !== token) {
					return;
				}
				if (!response || typeof response.code === 'undefined') {
					ekoUnlock(form, sourceBtn);
					ekoDisposeForm(form);
					ekoTip('alert', '结果未确认，请核对页面后再操作。');
					return;
				}
				var message = typeof response.data === 'string' ? response.data : '操作完成';
				if (response.code == 1 && after === 'stay') {
					ekoTip('msg', ekoText(message), 1600);
					ekoUnlock(form, sourceBtn);
					ekoDisposeForm(form);
					return;
				}
				if (response.code == 1) {
					var tourl = response.tourl || '';
					var match = form.getAttribute('data-nav-match');
					var go = false;
					if (after === 'nav' && ekoAdminUrl(tourl)) {
						go = true;
					}
					if (match && tourl.indexOf(match) !== -1 && ekoAdminUrl(tourl)) {
						go = true;
					}
					ekoTip('msg', ekoText(message), 800, function() {
						if (form.getAttribute('data-eko-left') === '1') {
							return;
						}
						form.setAttribute('data-eko-left', '1');
						ekoDisposeForm(form);
						if (go) {
							var dest = document.createElement('a');
							dest.href = tourl;
							if (dest.pathname === window.location.pathname && dest.search === window.location.search) {
								var nextHash = dest.hash || '';
								var nextUrl = dest.pathname + dest.search + nextHash;
								if ((window.location.hash || '') !== nextHash) {
									if (window.history && window.history.replaceState) {
										window.history.replaceState(null, '', nextUrl);
									} else {
										window.location.hash = nextHash ? nextHash.replace(/^#/, '') : '';
									}
								}
								window.location.reload();
							} else {
								window.location.href = tourl;
							}
						} else {
							window.location.reload();
						}
					});
					return;
				}
				ekoUnlock(form, sourceBtn);
				ekoDisposeForm(form);
				var failUrl = response.tourl || '';
				var failGo = after === 'nav' && ekoAdminUrl(failUrl);
				if (failGo) {
					ekoTip('alert', ekoText(message || '操作失败'), 0, function() {
						window.location.href = failUrl;
					});
				} else {
					ekoTip('alert', ekoText(message || '操作失败'));
				}
			},
			error: function(xhr, textStatus) {
				if (form.getAttribute('data-eko-token') !== token) {
					return;
				}
				ekoUnlock(form, sourceBtn);
				ekoDisposeForm(form);
				if (textStatus === 'timeout') {
					ekoTip('alert', '请求超时，结果未确认：操作可能已在服务端执行，请刷新页面或查看系统日志核实后再操作。');
					return;
				}
				ekoTip('alert', '结果未确认，请核对页面后再操作。');
			}
		});
	};

	document.addEventListener('click', function(e) {
		var el = e.target;
		while (el && el !== document) {
			if (el.form && el.tagName && (el.tagName === 'BUTTON' || el.tagName === 'INPUT')) {
				var submitLike = el.type === 'submit' || el.getAttribute('lay-submit') !== null;
				if (submitLike && el.className.indexOf('ajax-sorting') === -1 && el.form.className.indexOf('eko-ajax') !== -1) {
					el.form.__ekoSubmitter = el;
				}
				break;
			}
			el = el.parentNode;
		}
	}, true);

	$(document).on('click', '.eko-post', function(e) {
		e.preventDefault();
		var btn = this;
		if (btn.getAttribute('data-eko-lock') === '1' || btn.disabled) {
			return false;
		}
		var ask = btn.getAttribute('data-confirm');
		if (ask && !window.confirm(ask)) {
			return false;
		}
		var stale = document.querySelectorAll('form[data-eko-ephemeral="1"]');
		var si;
		for (si = 0; si < stale.length; si++) {
			if (stale[si].parentNode) {
				stale[si].parentNode.removeChild(stale[si]);
			}
		}
		var form = document.createElement('form');
		form.method = 'post';
		form.action = btn.getAttribute('data-action');
		form.className = 'eko-ajax';
		form.setAttribute('data-eko-ephemeral', '1');
		form.setAttribute('data-after', btn.getAttribute('data-after') || 'reload');
		var fields = {
			formcheck: btn.getAttribute('data-formcheck') || '',
			stay: btn.getAttribute('data-stay') || ''
		};
		if (btn.getAttribute('data-field')) {
			fields.field = btn.getAttribute('data-field');
			fields.value = btn.getAttribute('data-value') || '';
		}
		var name;
		for (name in fields) {
			if (!fields.hasOwnProperty(name)) {
				continue;
			}
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = fields[name];
			form.appendChild(input);
		}
		document.body.appendChild(form);
		window.ekoAjaxSubmit(form, {sourceBtn: btn, confirmed: true});
		return false;
	});

	$(document).on('submit', 'form.eko-ajax', function(e) {
		e.preventDefault();
		var submitter = null;
		if (e.originalEvent && e.originalEvent.submitter) {
			submitter = e.originalEvent.submitter;
		} else if (this.__ekoSubmitter) {
			submitter = this.__ekoSubmitter;
		}
		window.ekoAjaxSubmit(this, {
			sourceBtn: submitter
		});
		return false;
	});

    $('.ajaxlink').on("click",function(){
        var url=$(this).attr("href");
        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            data: {},
            success: function (response, status) {
                layer.msg(response.data);
                if(response.tourl!=""){
                    location.href=response.tourl;
                }
            },
            error:function(xhr,status,error){
                alert('返回数据异常！');
            }
        });
        return false;
    })

    // 排序保存成功后提示并刷新当前列表
    if (typeof layui !== 'undefined') {
        layui.use('layer', function () {
            var layer = layui.layer;
            $('.ajax-sorting').each(function () {
                var button = $(this);
                var form = button.closest('form');
                var sortUrl = form.attr('action');
                button.on('click', function () {
                    var data = form.serializeArray();
                    var inputs = form.find('input[name="sorting[]"]');
                    var editable = inputs.filter(':not([readonly])');
                    var buttons = form.find(':submit:enabled, .ajax-sorting:enabled');
                    var label = button.text();
                    var saved = false;
                    data.push({name: 'submit', value: 'sorting'});
                    editable.prop('readOnly', true);
                    buttons.prop('disabled', true);
                    button.text('保存中…');
                    $.ajax({
                        type: 'POST',
                        url: sortUrl,
                        dataType: 'json',
                        timeout: 30000,
                        data: data,
                        success: function (response) {
                            if (response && response.code == 1 && response.data && Array.isArray(response.data.sorting) && response.data.sorting.length === inputs.length) {
                                inputs.each(function (index) { $(this).val(response.data.sorting[index]); });
                                saved = true;
                                layer.msg('排序已保存', {icon: 1, time: 800}, function () {
                                    window.location.reload();
                                });
                            } else if (response && response.code == 0 && typeof response.data === 'string') {
                                layer.alert($('<div>').text(response.data).html(), {icon: 2});
                            } else {
                                layer.alert('返回数据异常，无法确认保存结果，请检查后重试。', {icon: 2});
                            }
                        },
                        error: function () {
                            layer.alert('请求失败或登录已失效，无法确认保存结果，请检查后重试。', {icon: 2});
                        },
                        complete: function () {
                            if (saved) return;
                            editable.prop('readOnly', false);
                            buttons.prop('disabled', false);
                            button.text(label);
                        }
                    });
                    return false;
                });
            });
        });
    }

    $('#sortTable, #menuTable').each(function () {
        initAdminTreeTable(this, {
            column: 1,
            indent: 20,
            stringExpand: '展开',
            stringCollapse: '收缩'
        });
    });

})

//对菜单进行高亮显示
function light_nav(){

    //二级菜单标记当前栏目
    var url = $('#url').data('url').toLowerCase();
    var controller = $('#controller').data('controller').toLowerCase();
    var mcode = $('#mcode').data('mcode');
    var aobj= $('#nav .nav-item').find('a');
    var flag = false;


    //第一种情况，url完全一致
    aobj.each(function (index, element) {
        var aUrl = $(element).attr('href').toLowerCase();
        if (url==aUrl) {
            $(element).parent("dd").addClass("layui-this");
            $(element).parents('.layui-nav-item').addClass('layui-nav-itemed');
            flag = true;
        }
        if(flag) return false;
    });

    url = url.replace('.html','');

    //第二种情况，菜单的子页面，如翻页
    if(!flag){
        aobj.each(function (index, element) {
            var aUrl = $(element).attr('href').toLowerCase();
            aUrl = aUrl.replace('.html','');
            if (url.indexOf(aUrl)>-1) {
                $(element).parent("dd").addClass("layui-this");
                $(element).parents('.layui-nav-item').addClass('layui-nav-itemed');
                flag = true;
            }
            if(flag) return false;
        });
    }

    //第三种情况，只匹配到模型，如模型栏目内容的修改操作页面
    if(!flag){
        aobj.each(function (index, element) {
            var aUrl = $(element).attr("href").toLowerCase();
            if (mcode && aUrl.indexOf('/mcode/'+mcode)>-1) {
                $(element).parent("dd").addClass("layui-this");
                $(element).parents('.layui-nav-item').addClass('layui-nav-itemed');
                flag = true;
            }
            if(flag) return false;
        });
    }

    //第四种情况，只匹配到控制器，如增、改的操作页面
    if(!flag){
        aobj.each(function (index, element) {
            var aUrl = $(element).attr("href").toLowerCase();
            if (controller!='index' && aUrl.indexOf('/'+controller+'/')>-1) {
                $(element).parent("dd").addClass("layui-this");
                $(element).parents('.layui-nav-item').addClass('layui-nav-itemed');
                flag = true;
            }
            if(flag) return false;
        });
    }

    //默认高亮
    if(!flag){
        $('#nav').find('.nav-item').eq(2).addClass('layui-nav-itemed');
    }
}


//判断option是否存在，如果不存在就增加
function addOptionValue(id,value,text) {
    if(!isExistOption(id,value)){$('#'+id).append("<option value="+value+">"+text+"</option>");}
}

//判断option是否存在
function isExistOption(id,value) {
    var isExist = false;
    var count = $('#'+id).find('option').length;
    for(var i=0;i<count;i++)
    {
        if($('#'+id).get(0).options[i].value == value)
        {
            isExist = true;
            break;
        }
    }
    return isExist;
}

function initAdminTreeTable(table, options) {
    var indent = options.indent;
    var column = options.column;
    var stringExpand = options.stringExpand;
    var stringCollapse = options.stringCollapse;
    var $table = $(table);
    var nodes = {};
    var order = [];

    $table.find('tbody > tr').each(function () {
        var id = this.getAttribute('data-tt-id');
        if (id === null || id === '') {
            return;
        }
        id = String(id);
        var parentId = this.getAttribute('data-tt-parent-id');
        parentId = parentId === null ? '' : String(parentId);
        var node = {
            id: id,
            parentId: parentId,
            el: this,
            children: [],
            collapsed: true,
            isRoot: true
        };
        nodes[id] = node;
        order.push(node);
    });

    var i;
    for (i = 0; i < order.length; i++) {
        var node = order[i];
        if (node.parentId && nodes[node.parentId]) {
            nodes[node.parentId].children.push(node);
            node.isRoot = false;
        }
    }

    function levelOf(item) {
        var n = 0;
        var guard = 0;
        var current = item;
        while (current.parentId && nodes[current.parentId] && guard < 50) {
            n++;
            current = nodes[current.parentId];
            guard++;
        }
        return n;
    }

    function hideDescendants(item) {
        var c;
        for (c = 0; c < item.children.length; c++) {
            $(item.children[c].el).hide();
            hideDescendants(item.children[c]);
        }
    }

    function showChildren(item) {
        var c;
        for (c = 0; c < item.children.length; c++) {
            var child = item.children[c];
            $(child.el).show();
            if (!child.collapsed) {
                showChildren(child);
            }
        }
    }

    function setCollapsed(item, collapsed) {
        var $exp = $(item.el).find('a.tree-expander');
        item.collapsed = collapsed;
        if (collapsed) {
            $(item.el).removeClass('expanded').addClass('collapsed');
            $exp.attr('title', stringExpand).removeClass('fa-caret-down').addClass('fa-caret-right');
            hideDescendants(item);
        } else {
            $(item.el).removeClass('collapsed').addClass('expanded');
            $exp.attr('title', stringCollapse).removeClass('fa-caret-right').addClass('fa-caret-down');
            showChildren(item);
        }
    }

    for (i = 0; i < order.length; i++) {
        (function (item) {
            var $cell = $(item.el).children('td').eq(column);
            var $indenter = $('<span class="indenter"></span>').css('paddingLeft', (levelOf(item) * indent) + 'px');
            if (item.children.length) {
                var $expander = $('<a href="#" class="tree-expander fa fa-caret-right">&nbsp;</a>');
                $expander.attr('title', stringExpand);
                $expander.on('click', function (e) {
                    e.preventDefault();
                    setCollapsed(item, !item.collapsed);
                });
                $indenter.append($expander);
                $(item.el).addClass('collapsed');
            }
            $cell.prepend($indenter);
            if (!item.isRoot) {
                $(item.el).hide();
            }
        })(order[i]);
    }

    $table.addClass('admin-tree');
} 
