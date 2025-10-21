<template>
    <div class="footer-bottom">
        <div class="site-info">
           <a v-if="copyRight.icp" class="foot-link" href="" target="_blank">首页</a> 
		   <a v-if="copyRight.icp" class="foot-link" href="" target="_blank">产品中心</a> 
		   <a v-if="copyRight.icp" class="foot-link" href="" target="_blank">源码下载</a> 
		   <a v-if="copyRight.icp" class="foot-link" href="" target="_blank">授权价格</a> 
		   <a v-if="copyRight.icp" class="foot-link" href="" target="_blank">解决方案</a> 
		   <a v-if="copyRight.icp" class="foot-link" href="" target="_blank">商城资讯</a> 
		   <a v-if="copyRight.icp"  style="border: none;" class="foot-link" href="" target="_blank">服务支持</a>
        </div>
        <p>
            {{ copyRight.copyright_desc }}
			<a v-if="copyRight.icp" class="footer-link" href="" target="_blank">版权所有©2019-2020 {{copyRight.company_name}}并保留所有权利<!-- 山西牛酷网络科技信息有限公司并保留所有权利 --></a>
            <a v-if="copyRight.icp" style="border: none;" class="footer-link" href="https://beian.miit.gov.cn" target="_blank">备案号：{{ copyRight.icp }}</a>
        </p>
        <p>
            <a v-if="copyRight.gov_record" style="border: none;" class="footer-link" :href="copyRight.gov_url" target="_blank">
                <img src="@/assets/images/gov_record.png" alt="公安备案" />
                <span>{{ copyRight.gov_record }}</span>
            </a>
        </p>
    </div>
</template>

<script>
    import { copyRight } from "@/api/website"
    import { mapGetters } from "vuex"
    export default {
        props: {},
        data() {
            return {}
        },
        computed: {},
        created() {
            this.$store.dispatch("site/copyRight")
        },
        mounted() {},
        watch: {},
        methods: {},
        computed: {
            ...mapGetters(["copyRight", "siteInfo"])
        }
    }
</script>

<style scoped lang="scss">
    .footer-bottom {
        width: 100%;
        height: 85px;
        margin: 0 auto;
        padding: 20px 0;
		.foot-link {
			padding:0 10px;
			border-right:1px solid #b3b3b3;
			height: 14px; 
			line-height: 14px;
			margin:120px 0 10px 0;
			color: #b3b3b3;
		}
		.footer-link {
			padding:0 10px;
			border-right:1px solid #b3b3b3;
			margin-bottom: 10px;
			p {
				
			}
		}
        p {
            margin: 0;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
			
            img {
                width: 20px;
                height: 20px;
                margin-right: 5px;
            }
            .footer-link, .el-link.el-link--default {
               color: #b3b3b3 !important;
            }
			.footer-link:hover, .el-link.el-link--default:hover {
				color: $base-color;
			}
        }
        .site-info {
            // display: flex;
            // justify-content: center;
            // align-items: center;
			text-align: center;
            p {
                width: auto;
                margin: 0 10px;

                i {
                    vertical-align: bottom;
                    margin-right: 5px;
                }
            }
        }
    }
</style>
