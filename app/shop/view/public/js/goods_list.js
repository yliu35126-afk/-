var laytpl, form, element, repeat_flag, table;
$(function () {

	$("body").on("click", ".contraction", function () {
		var goods_id = $(this).attr("data-goods-id");
		var open = $(this).attr("data-open");
		var open_t = $(this).siblings('.ns-title-content').children('.vips_price').attr("data-open") ? $(this).siblings('.ns-title-content').children('.vips_price').attr("data-open") : 0;
		var tr = $(this).parent().parent().parent().parent();
		var index = tr.attr("data-index");

		if ((parseInt(open)+parseInt(open_t)) > 0) {
			$(this).children("span").text("+");
			$(".js-sku-list-" + index).remove();
		} else {
			$(this).children("span").text("-");
			$.ajax({
				url: ns.url("shop/goods/getGoodsSkuList"),
				data: {goods_id: goods_id},
				dataType: 'JSON',
				type: 'POST',
				async: false,
				success: function (res) {
					var list = res.data;
					var sku_list = $("#skuList").html();
					var data = {
						list: list,
						index: index,
						member_price_is_exit: member_price_is_exit,

					};
					laytpl(sku_list).render(data, function (html) {
						tr.after(html);
					});
					
					layer.photos({
					  	photos: '.img-wrap',
						anim: 5
					});
				}
			});
		}
		$(this).attr("data-open", (open == 0 ? 1 : 0));
		$(this).siblings('.ns-title-content').children('.vips_price').attr("data-open", (open == 0 ? 1 : 0));
	});
	$("body").on("click", ".vips_price", function () {
		var goods_id = $(this).attr("data-goods-id");
		var open = $(this).attr("data-open");
		var open_t = $(this).parent().siblings('.contraction').attr("data-open");
		var tr = $(this).parent().parent().parent().parent().parent();
		var index = tr.attr("data-index");

		if ((parseInt(open)+parseInt(open_t)) > 0) {
			$(this).parent().siblings('.contraction').children("span").text("+");
			$(".js-sku-list-" + index).remove();
		} else {
			$(this).parent().siblings('.contraction').children("span").text("-");
			$.ajax({
				url: ns.url("shop/goods/getGoodsSkuList"),
				data: {goods_id: goods_id},
				dataType: 'JSON',
				type: 'POST',
				async: false,
				success: function (res) {
					var list = res.data;
					var sku_list = $("#skuList").html();
					var data = {
						list: list,
						index: index,
						member_price_is_exit: member_price_is_exit,

					};
					laytpl(sku_list).render(data, function (html) {
						tr.after(html);
					});

					layer.photos({
						photos: '.img-wrap',
						anim: 5
					});
				}
			});
		}
		$(this).attr("data-open", (open == 0 ? 1 : 0));
		$(this).parent().siblings('.contraction').attr("data-open", (open == 0 ? 1 : 0))
	});
	layui.use(['form', 'laytpl', 'element'], function () {
		form = layui.form;
		repeat_flag = false; //防重复标识
		element = layui.element;
		laytpl = layui.laytpl;
		
		form.render();
		refreshTable(0);
		
		//监听Tab切换，以改变地址hash值
		element.on('tab(goods_list_tab)', function () {
			var type = this.getAttribute('data-type');
			$("input[name='goods_state']").val("");
			$("input[name='verify_state']").val("");
			if (type) {
				var id = this.getAttribute('lay-id');
				$("input[name='" + type + "']").val(id);
			}
			var html = '<button class="layui-btn layui-btn-primary" lay-event="delete">批量删除</button>';
			if (type == "goods_state" && id == 1) {
				// 销售中状态：下架
				html += '<button class="layui-btn layui-btn-primary" lay-event="off_goods">批量下架</button>';
				$("input[name='verify_state']").val(1);
			} else if (type == "goods_state" && id == 0) {
				// 仓库中状态：上架
				html += '<button class="layui-btn layui-btn-primary" lay-event="on_goods">批量上架</button>';
				$("input[name='verify_state']").val(1);
			}

			html += '<button class="layui-btn layui-btn-primary" lay-event="batch_set">批量设置</button>';

			$("#toolbarOperation").html(html);
			$("#batchOperation").html(html);
			
			// 全部、销售中、仓库中、待审核
			if (type == null || type == "goods_state" || (type == "verify_state" && id == 0)) {
				refreshTable(0);
			} else if (type == "verify_state") {
				// 审核失败、违规下架
				refreshTable(1);
			}
			
		});
		
		// 监听工具栏操作
		table.tool(function (obj) {
			var data = obj.data;
			var curr = $(".layui-laypage-em").next().html();
			switch (obj.event) {
				//会员价
				case 'member_price':

					var url = ns.url("memberprice://shop/goods/config", {goods_id: data.goods_id});
					// alert(url);
					// exit;
					//iframe层-父子操作
					var layerIndex = layer.open({
						title: "自定义会员价",
						type: 2,
						area: ['1100px', '650px'],
						// btn: '保存',
						content: url,
						success: function(layero, index){
							var dom_save = layer.getChildFrame("#save_member_price", index);
							var dom_back = layer.getChildFrame("#back_goods_list", index);
							var iframeWin = window[layero.find('iframe')[0]['name']];//得到iframe页的窗口对象，执行iframe页的方法
							$(dom_save).click(function(){
								setTimeout(function(){
									iframeWin.formSubmit(function(data){
										if (data == 1) {
											layer.close(layerIndex);
											table.reload();
										}
									});
								},300)
							});
							$(dom_back).click(function(){
								layer.close(layerIndex);
							})
						}
					});

					break;

				case 'select': //推广
					goodsUrl(data);
					break;
				case 'preview': //预览
					goodsPreview(data);
					break;
				case 'edit':
					//编辑`
					if (data.goods_class == 1) {
						window.open(ns.url("shop/goods/editgoods", {"goods_id": data.goods_id}));
					} else {
						window.open(ns.url("shop/virtualgoods/editgoods", {"goods_id": data.goods_id}));
					}
					break;
				case 'delete':
					//删除
					deleteGoods(data.goods_id);
					break;
				case 'off_goods':
					//下架
					offGoods(data.goods_id);
					break;
				case 'on_goods':
					//上架
					onGoods(data.goods_id);
					break;
				case 'editStock':
					editStock(data);
					break;
				case 'copy':
					copyGoods(data.goods_id);
					break;
				case 'browse_records':
					location.href = ns.url("shop/goods/goodsBrowse", {goods_id:data.goods_id});
					break;
				case 'select_violations_remark':
					getVerifyStateRemark(data.goods_id, 10);
					break;
			}
		});
		
		// 提交修改后的库存
		form.on('submit(edit_stock)', function (obj) {
			var field = obj.field;
			if (repeat_flag) return false;
			repeat_flag = true;
			
			$.ajax({
				type: "POST",
				url: ns.url("shop/goods/editGoodsStockPrice"),
				data: {
					'sku_list': field
				},
				dataType: 'JSON',
				success: function (res) {
					layer.msg(res.message);
					repeat_flag = false;
					if (res.code == 0) {
						table.reload({
							page: {
								curr: $(".layui-laypage-em").next().html()  //主要代码行
						   },
						});
						refreshVerifyStateCount();
						layer.closeAll('page');
					}
				}
			});
		});

		// 批量操作
		table.toolbar(function (obj) {

			if (obj.data.length < 1) {
				layer.msg('请选择要操作的数据');
				return;
			}
			var id_array = new Array();
			for (i in obj.data) id_array.push(obj.data[i].goods_id);
			switch (obj.event) {
				case "delete":
					deleteGoods(id_array.toString());
					break;
				case 'off_goods':
					//下架
					offGoods(id_array.toString());
					break;
				case 'on_goods':
					//上架
					onGoods(id_array.toString());
					break;
				case 'batch_set':
					layer.open({
						title: "批量设置",
						type: 1,
						area: ['700px', '600px'],
						content: $('#batchSet').html(),
						success: function(){

							//增加店内分类下拉框
							$("body").on('click', '.add_shop_category', function(){
								laytpl($("#shop_category_html").html()).render([], function (html) {
									$(".js-goods-shop-category").append(html);
									form.render();
								})
							});

							//删除店内分类
							$("body").on('click', '.js-goods-shop-category .layui-icon-close', function(){
								$(this).parent().remove();
							});

							form.on('select(batch_shop_category)', function(data){
								var shop_category = [];
								var this_index = $(data.elem).parent().index();
								$(".shop_category_class").each(function(){
									var index = $(this).parent().index();
									if(this_index != index){
										shop_category.push($(this).val());
									}
								});
								if(data.value != 0 && $.inArray(data.value, shop_category) != -1){
									$(data.elem).val(0);
									form.render();
									layer.msg("已存在该分类，不可重复添加");
								}
							});
							form.render();
						}
					});
					break;
			}
		});
		// 批量操作
		table.bottomToolbar(function (obj) {
			
			if (obj.data.length < 1) {
				layer.msg('请选择要操作的数据');
				return;
			}
			var id_array = new Array();
			for (i in obj.data) id_array.push(obj.data[i].goods_id);
			switch (obj.event) {
				case "delete":
					deleteGoods(id_array.toString());
					break;
				case 'off_goods':
					//下架
					offGoods(id_array.toString());
					break;
				case 'on_goods':
					//上架
					onGoods(id_array.toString());
					break;
				case 'batch_set':
					layer.open({
						title: "批量设置",
						type: 1,
						area: ['700px', '600px'],
						content: $('#batchSet').html(),
						success: function(){

							//增加店内分类下拉框
							$("body").on('click', '.add_shop_category', function(){
								laytpl($("#shop_category_html").html()).render([], function (html) {
									$(".js-goods-shop-category").append(html);
									form.render();
								})
							});

							//删除店内分类
							$("body").on('click', '.js-goods-shop-category .layui-icon-close', function(){
								$(this).parent().remove();
							});

							form.on('select(batch_shop_category)', function(data){
								var shop_category = [];
								var this_index = $(data.elem).parent().index();
								$(".shop_category_class").each(function(){
									var index = $(this).parent().index();
									if(this_index != index){
										shop_category.push($(this).val());
									}
								});
								if(data.value != 0 && $.inArray(data.value, shop_category) != -1){
									$(data.elem).val(0);
									form.render();
									layer.msg("已存在该分类，不可重复添加");
								}
							});
							form.render();
						}
					});
					break;
			}
		});

        table.on("sort",function (obj) {
            table.reload({
                page: {
                    curr: 1
                },
                where: {
                    order:obj.field,
                    sort:obj.type
                }
            });
        });

		// 搜索功能
		form.on('submit(search)', function (data) {
			table.reload({
				page: {
					curr: 1
				},
				where: data.field
			});
			refreshVerifyStateCount();
			return false;
		});

		// 导出功能
		form.on('submit(export)', function (data) {

			exportGoods(data.field);
			return false;
		});
		
		// 验证
		form.verify({
			int: function (value) {
				if (value < 0) {
					return '销量不能小于0!'
				}
				if (value % 1 != 0) {
					return '销量不能为小数!'
				}
			},
		})
		
	});

	$('body').on('click', '.batch-set-wrap .tab-wrap li', function(){
		var type = $(this).attr('data-type');
		$(this).addClass('active').siblings('li').removeClass('active');
		$('.batch-set-wrap .content-wrap .tab-item.'+ type).addClass('tab-show').siblings('.tab-item').removeClass('tab-show');
		$('.batch-set-wrap .footer-wrap').show();
	});

	$('body').on('click', '.batch-set-wrap .shipping .layui-form-radio', function(){
		if ($('[name="is_free_shipping"]:checked').val() == 1) {
			$('.batch-set-wrap .shipping .shipping_template').hide();
		} else {
			$('.batch-set-wrap .shipping .shipping_template').show();
		}
	})

});

/**
 * 刷新表格列表
 * @param status 状态：0 在售，1 审核违规
 */
function refreshTable(status) {
	var page = $(".page").val();
	var cols = [
		[{
			type: 'checkbox',
			unresize: 'false',
			width: '3%'
		}, {
			title: '商品信息',
			unresize: 'false',
			width: '22%',
			templet: '#goods_info'
		}, {
			field: 'price',
			title: '<span style="padding-right: 15px;">价格(元)</span>',
			unresize: 'false',
			width: '8%',
			align: 'right',
			templet: function (data) {
				return '<span style="padding-right: 15px;">￥' + data.price + '</span>';
			}
		}, {
			field: 'goods_stock',
			title: '库存',
			unresize: 'false',
			width: '10%',
			sort:true
		}, {
			field: 'sale_num',
			title: '销量',
			unresize: 'false',
			width: '10%',
            sort:true
		}, {
			title: '商品状态',
			unresize: 'false',
			width: '15%',
			templet: function (data) {
				var str = '';
				if (data.goods_state == 1 && data.verify_state == 1) {
					str = '销售中';
				} else if (data.goods_state == 0 && data.verify_state == 1) {
					str = '仓库中';
				}else if (data.verify_state === 0) {
					str = '审核中';
				}else if (data.verify_state === -2) {
					str = '审核失败';
				}else if (data.verify_state === 10) {
					str = '违规下架';
					str +='<a class="btn-color" lay-event="select_violations_remark">（查看）</a>'
				}
				return str;
			}
		}, {
			title: '创建时间',
			unresize: 'false',
			width: '17%',
			templet: function (data) {
				return ns.time_to_date(data.create_time);
			}
		}, {
			title: '操作',
			toolbar: '#action',
			unresize: 'false',
			width: '15%'
		}]
	];
	if (status === 1) {
		cols = [
			[{
				type: 'checkbox',
				unresize: 'false',
				width: '3%'
			}, {
				title: '商品信息',
				unresize: 'false',
				width: '22%',
				templet: '#goods_info'
			}, {
				title: '审核状态',
				unresize: 'false',
				width: '15%',
				templet: function (data) {
					var str = '';
					if (data.verify_state == 1) {
						str = '已审核';
					} else if (data.verify_state == 0) {
						str = '待审核';
					} else if (data.verify_state == 10) {
						str = '违规下架';
						str +='<a class="btn-color" lay-event="select_violations_remark">（查看）</a>'
					} else if (data.verify_state == -1) {
						str = '审核中';
					} else if (data.verify_state == -2) {
						str = '审核失败';
					}
					return str;
				}
			}, {
				title: '创建时间',
				unresize: 'false',
				width: '25%',
				templet: function (data) {
					return ns.time_to_date(data.create_time);
				}
			}, {
				title: '操作',
				toolbar: '#action',
				unresize: 'false',
				width: '15%'
			}]
		];
	}
	
	table = new Table({
		elem: '#goods_list',
		url: ns.url("shop/goods/lists"),
		cols: cols,
		toolbar: "#toolbarOperation",
		bottomToolbar: "#batchOperation",
		where: {
			search_text: $("input[name='search_text']").val(),
			goods_state: $("input[name='goods_state']").val(),
			verify_state: $("input[name='verify_state']").val(),
			start_sale: $("input[name='start_sale']").val(),
			end_sale: $("input[name='end_sale']").val(),
			// start_price: 0,
			// end_price: 0,
			goods_shop_category_ids: $("select[name='goods_shop_category_ids'] option:checked").val(),
			category_id: $("input[name='category_id']").val(),
			goods_class: $("select[name='goods_class'] option:checked").val(),
			promotion_type: $("select[name='promotion_type'] option:checked").val(),//参与活动
		}
	});
	
	refreshVerifyStateCount();
}

function add() {
	location.href = ns.url('shop/goods/addGoods');
}
function grab() {
	location.href = ns.url('goodsgrab://shop/goodsgrab/lists');
}
// 删除
function deleteGoods(goods_ids) {
	layer.confirm('删除后进入回收站，确定删除吗?', function () {
		if (repeat_flag) return;
		repeat_flag = true;
		
		$.ajax({
			url: ns.url("shop/goods/deleteGoods"),
			data: {goods_ids: goods_ids.toString()},
			dataType: 'JSON',
			type: 'POST',
			success: function (res) {
				layer.msg(res.message);
				repeat_flag = false;
				if (res.code == 0) {
					table.reload({
						page: {
							curr: $(".layui-laypage-em").next().html()  //主要代码行
					    },
					});
					refreshVerifyStateCount();
				}
			}
		});
	});
}

//商品下架
function offGoods(goods_ids) {
	if (repeat_flag) return;
	repeat_flag = true;
	
	$.ajax({
		url: ns.url("shop/goods/offGoods"),
		data: {goods_state: 0, goods_ids: goods_ids.toString()},
		dataType: 'JSON',
		type: 'POST',
		success: function (res) {
			layer.msg(res.message);
			repeat_flag = false;
			if (res.code == 0) {
				table.reload();
				refreshVerifyStateCount();
			}
		}
	});
}

//商品上架
function onGoods(goods_ids) {
	
	if (repeat_flag) return;
	repeat_flag = true;
	
	$.ajax({
		url: ns.url("shop/goods/onGoods"),
		data: {goods_state: 1, goods_ids: goods_ids.toString()},
		dataType: 'JSON',
		type: 'POST',
		success: function (res) {
			layer.msg(res.message);
			repeat_flag = false;
			if (res.code == 0) {
				table.reload();
				refreshVerifyStateCount();
			}
		}
	});
}

// 编辑库存
function editStock(data) {
	$.ajax({
		type: "POST",
		url: ns.url("shop/goods/getGoodsSkuList"),
		data: {
			'goods_id': data.goods_id,
		},
		dataType: 'JSON',
		success: function (res) {
			laytpl($("#edit_stock").html()).render(res.data, function (html) {
				layer_stock = layer.open({
					title: '修改库存',
					skin: 'layer-tips-class',
					type: 1,
					area: ['1000px'],
					content: html,
				});
			});
		}
	});
	
}

// 商品推广
function goodsUrl(data) {
	$(".operation-wrap[data-goods-id='" + data.goods_id + "'] .popup-qrcode-wrap").css("display", "block");
	$('#goods_name').html(data.goods_name);
	$.ajax({
		type: "POST",
		url: ns.url("shop/goods/goodsUrl"),
		data: {
			'goods_id': data.goods_id
		},
		dataType: 'JSON',
		success: function (res) {
			if (res.data.path.h5.status == 1) {
				res.data.goods_id = data.goods_id;
				laytpl($("#goods_url").html()).render(res.data, function (html) {
					$(".operation-wrap[data-goods-id='" + data.goods_id + "'] .popup-qrcode-wrap").html(html).show();
					
					$("body").click(function (e) {
						if (!$(e.target).closest(".popup-qrcode-wrap").length) {
							$(".operation-wrap[data-goods-id='" + data.goods_id + "'] .popup-qrcode-wrap").hide();
						}
					});
				});
			} else {
				layer.msg(res.data.path.h5.message);
			}
		}
	});
	
}

// 商品预览
var isOpenGoodsPreviewPopup = false;//防止重复弹出商品预览框
function goodsPreview(data) {
	if (isOpenGoodsPreviewPopup) return;
	isOpenGoodsPreviewPopup = true;
	$.ajax({
		type: "POST",
		url: ns.url("shop/goods/goodsPreview"),
		data: {
			'goods_id': data.goods_id
		},
		dataType: 'JSON',
		success: function (res) {
			if (res.data.path.h5.status == 1) {
				res.data.goods_id = data.goods_id;
				
				laytpl($("#goods_preview").html()).render(res.data, function (html) {
					var layerIndex = layer.open({
						title: '商品预览',
						skin: 'layer-tips-class',
						type: 1,
						area: ['600px', '600px'],
						content: html,
						success: function () {
							isOpenGoodsPreviewPopup = false;
						}
					});
				});
			} else {
				layer.msg(res.data.path.h5.message);
			}
		}
	});
}

/**
 * 获取违规下架原因
 * @param goods_id
 * @param verify_state
 */
function getVerifyStateRemark(goods_id, verify_state) {
	$.ajax({
		url: ns.url("shop/goods/getVerifyStateRemark"),
		data: {goods_id: goods_id},
		dataType: 'JSON',
		type: 'POST',
		success: function (res) {
			var data = res.data;
			if (data) {
				var title = '';
				if (verify_state == -2) title = '审核失败原因';
				else title = '违规下架原因';
				layer.open({
					title: title,
					content: data.verify_state_remark,
					cancel: function (index, layero) {
						repeat_flag = false;
					},
					end: function () {
						repeat_flag = false;
					},
					yes: function (index, layero) {
						repeat_flag = false;
						layer.close(index);
					}
				});
			}
		}
	});
	
}

/**
 * 刷新审核状态商品数量
 */
function refreshVerifyStateCount() {
	$.ajax({
		url: ns.url("shop/goods/refreshVerifyStateCount"),
		type: 'POST',
		dataType: 'JSON',
		success: function (res) {
			for (var i = 0; i < res.length; i++) {
				if (res[i].count) $('div[lay-filter="goods_list_tab"] li[data-type="verify_state"][lay-id="' + res[i].state + '"] span.count').text(res[i].count).show();
				else $('div[lay-filter="goods_list_tab"] li[data-type="verify_state"][lay-id="' + res[i].state + '"] span').hide();
			}
		}
	});
}

function closeStock() {
	layer.close(layer_stock);
}

//复制商品
function copyGoods(goods_id){
	layer.confirm('确定复制当前商品吗?', function() {
		$.ajax({
			url: ns.url("shop/goods/copygoods"),
			data: {goods_id : goods_id},
			dataType: 'JSON',
			type: 'POST',
			success: function(res) {
				layer.msg(res.message);

				if (res.code >= 0) {
					table.reload();
					refreshVerifyStateCount();
					// location.reload();
				}
			}
		});
	}, function () {
		layer.close();
	});
}

/**
 * 导出商品
 * @param field
 */
function exportGoods(field){
	layer.confirm('确定按当前条件导出吗?', {title:'商品导出'},function() {
		$.ajax({
			url: ns.url("shop/goods/exportgoods"),
			data: field,
			dataType: 'JSON',
			type: 'POST',
			success: function(res) {
				layer.msg(res.message);

				if (res.code >= 0) {
					window.open(ns.url('shop/goods/export'));
				}
			}
		});
	}, function () {
		layer.close();
	});
}


// 批量设置
var setSub = false;
function batchSetting(){
	var id_array = new Array(),
		setType = $('.batch-set-wrap .tab-wrap .active').attr('data-type'),
		checkedData = table.checkStatus('goods_list').data,
		field = {},
		regExp = {
			number: /^\d{0,10}$/,
			digit: /^\d{0,10}(.?\d{0,2})$/
		};
	for (i in checkedData) id_array.push(checkedData[i].goods_id);

	switch(setType){
		case 'category':
			field.category = $('[name="batch_category"]').val();
			if(field.category <= 0){
				layer.msg('商品分类不能为空!');
				return;
			}
			break;
		case 'shop_category':
			var shop_category = [];

			$(".shop_category_class").each(function(){
				shop_category.push($(this).val());
			});
			// if(shop_category.length == 0){
			// 	layer.msg('商品分类不能为空!');
			// 	return;
			// }
			field.shop_category = shop_category.toString();
			break;
		case 'limit':
			field.max_buy = $('[name="batch_max_buy"]').val();
			field.min_buy = $('[name="batch_min_buy"]').val();
			if (isNaN(field.max_buy) || !regExp.number.test(field.max_buy)) {
				layer.msg('限购数量格式输入错误');
				return;
			}
			if (field.max_buy < 0) {
				layer.msg('限购数量不能小于0');
				return;
			}
			if (isNaN(field.min_buy) || !regExp.number.test(field.min_buy)) {
				layer.msg('起售数量格式输入错误');
				return;
			}
			if (field.min_buy < 0) {
				layer.msg('起售数量不能小于0');
				return;
			}
			if (parseInt(field.min_buy) > parseInt(field.max_buy) && parseInt(field.max_buy) > 0) {
				layer.msg('起售数量不能小于限购数量');
				return;
			}
			break;
		case 'shipping':
			field.is_free_shipping = $('[name="is_free_shipping"]:checked').val();
			field.shipping_template = $('[name="batch_shipping_template"]').val();
			if (field.is_free_shipping == 0 && field.shipping_template == 0) {
				layer.msg('请选择运费模板');
				return;
			}
			break;
	}

	if (setSub) return;
	setSub = true;

	field.type = setType;
	field.goods_ids = id_array.toString();
	$.ajax({
		type: "POST",
		url: ns.url("shop/goods/batchGoods"),
		data: field,
		dataType: 'JSON',
		success: function (res) {
			setSub = false;
			if (res.code >= 0) {
				$('.batch-set-wrap .footer-wrap').hide();
				$('.batch-set-wrap .content-wrap .tab-item.result').addClass('tab-show').siblings('.tab-item').removeClass('tab-show');
			} else {
				layer.msg('操作失败');
			}
		}
	})
}