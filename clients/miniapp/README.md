# WeChat 小程序接入说明

将你的小程序源码拷贝到 `clients/miniapp/src/`。

推荐结构：
- `clients/miniapp/src/` 小程序全部源码（`app.json`、`project.config.json`、页面等）
- 构建/发布目标：直接使用微信开发者工具运行；如需联动到本项目静态目录，可将需要的静态素材拷到 `public/weapp/`（非必须）

接口配置：
- 将接口 `BaseURL` 指向 Niushop 后端域名，例如：`https://your-domain.com`
- 快速联调可先用现有插件接口：
  - 活动信息：`/turntable/api/turntable/info`
  - 抽奖：`/turntable/api/turntable/lottery`
- 按《规划3.0.md》切换至 3.0 路由：`/addons/turntable/api/*`

认证：
- 使用 Niushop 原生会员 `token`（更快），或按需做服务端 SSO 映射。

Git 管理：
- 该目录建议作为独立仓库或子模块；构建产物不要提交到 PHP 仓库。