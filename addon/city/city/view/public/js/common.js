/**
 * 打开相册
 */
function openAlbum(callback, imgNum) {
	layui.use(['layer'], function () {
		//iframe层-父子操作
		layer.open({
			type: 2,
			title: '图片管理',
			area: ['825px', '675px'],
			fixed: false, //不固定
			btn: ['保存', '返回'],
			content: ns.url("city://city/album/album?imgNum=" + imgNum),
			yes: function (index, layero) {
				var iframeWin = window[layero.find('iframe')[0]['name']];//得到iframe页的窗口对象，执行iframe页的方法：

				iframeWin.getCheckItem(function (obj) {
					if (typeof callback == "string") {
						try {
							eval(callback + '(obj)');
							layer.close(index);
						} catch (e) {
							console.error('回调函数' + callback + '未定义');
						}
					} else if (typeof callback == "function") {
						callback(obj);
						layer.close(index);
					}

				});
			}
		});
	});
}