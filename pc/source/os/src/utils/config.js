export default {
	// api请求地址
	baseUrl: '{{$baseUrl}}',
	// 图片域名
	imgDomain: '{{$imgDomain}}',
	// 腾讯地图key
	mpKey: '{{$mpKey}}',
	// 客服
	webSocket: '{{$webSocket}}',
	// api安全
	apiSecurity: "{{$apiSecurity}}",
	//本地端主动给服务器ping的时间, 0 则不开启 , 单位秒
	pingInterval: 1500,
	// 公钥
	publicKey: `{{$publicKey}}`,
}