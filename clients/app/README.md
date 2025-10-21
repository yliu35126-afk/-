# App（原生/Flutter/React Native）接入说明

将你的 App 源码拷贝到 `clients/app/src/`。

建议：
- 保持 App 工程独立构建与打包（Android/iOS），不要将 APK/IPA 等产物提交到本仓库。
- H5 相关静态资源如需联动，可投放到 `public/h5/indep/` 或 `public/h5/default/`，并通过 WebView 加载。

接口配置：
- 将接口 `BaseURL` 指向 Niushop 后端域名，例如：`https://your-domain.com`
- 快速联调可先用现有插件接口：
  - 活动信息：`/turntable/api/turntable/info`
  - 抽奖：`/turntable/api/turntable/lottery`
- 按《规划3.0.md》切换至 3.0 路由：`/addons/turntable/api/*`

认证与会员：
- 直接用 Niushop 原生会员 `token`（更快），或做服务端 SSO 映射以复用你现有会员体系。

Git 管理：
- 建议作为独立仓库或子模块接入；将构建产物、依赖缓存（如 `build/`, `dist/`, `node_modules/` 等）写入各自工程的 `.gitignore`。