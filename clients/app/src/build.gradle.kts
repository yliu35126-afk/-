plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
}

// 构建完成后复制并重命名 APK，追加时间戳避免文件名重复（Windows 友好）
// 局域网IP注入：可通过 Gradle 属性 LAN_IP 或环境变量注入
val LAN_IP: String = (project.findProperty("LAN_IP") as String?)
    ?: System.getenv("LAN_IP")
    ?: "10.0.2.2"

android {
    namespace = "com.example.myapplication"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.yiqilishi.lottery.app"  // 使用项目专用的包名
        minSdk = 21  // 标准包保持21
        targetSdk = 33  // 满足Google Play要求的最低版本
        versionCode = 5
        versionName = "1.4"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    // 为老机顶盒/电视盒子提供 API19（Android 4.4）兼容包
    flavorDimensions += listOf("api")
    productFlavors {
        create("legacy19") {
            dimension = "api"
            minSdk = 21  // 修复：Material库要求最低API 21
        }
        create("standard21") {
            dimension = "api"
            minSdk = 21
        }
    }

    signingConfigs {
        create("release") {
            // 使用调试签名配置，方便安装测试
            storeFile = file("${System.getProperty("user.home")}/.android/debug.keystore")
            storePassword = "android"
            keyAlias = "androiddebugkey"
            keyPassword = "android"
            // 显式启用所有签名方案，兼容老设备（V1）与新设备（V2/V3）
            enableV1Signing = true
            enableV2Signing = true
        }
    }

    buildTypes {
        debug {
            applicationIdSuffix = ".debug"
            isDebuggable = true
            // 本地联调：HTTP API 走 8000；Socket.IO 固定走 3001
            buildConfigField("String", "BACKEND_BASE_URL", "\"http://${LAN_IP}:8000/index.php/api/\"")
            buildConfigField("String", "SOCKET_BASE_URL", "\"http://${LAN_IP}:3001\"")
            buildConfigField("String", "SOCKET_PATH", "\"/socket.io/\"")
            buildConfigField("int", "LOTTERY_DURATION_MS", "4000")
        }
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            signingConfig = signingConfigs.getByName("release")
            buildConfigField("String", "BACKEND_BASE_URL", "\"https://yiqilishi.com.cn/index.php/api/\"")
            // ✅ 按你的要求改为 HTTPS，不带端口，走 Nginx 443 反代
            buildConfigField("String", "SOCKET_BASE_URL", "\"https://yiqilishi.com.cn\"")
            buildConfigField("String", "SOCKET_PATH", "\"/socket.io/\"")
            buildConfigField("int", "LOTTERY_DURATION_MS", "9000")
        }
        create("releaseAlt") {
            initWith(getByName("release"))
            isMinifyEnabled = false
            signingConfig = signingConfigs.getByName("release")
            applicationIdSuffix = ".prod"
            versionNameSuffix = "-prod"
            matchingFallbacks += listOf("release")
            buildConfigField("String", "BACKEND_BASE_URL", "\"https://yiqilishi.com.cn/index.php/api/\"")
            // ✅ 同步为 HTTPS 不带端口
            buildConfigField("String", "SOCKET_BASE_URL", "\"https://yiqilishi.com.cn\"")
            buildConfigField("String", "SOCKET_PATH", "\"/socket.io/\"")
            buildConfigField("int", "LOTTERY_DURATION_MS", "9000")
        }
        // 备用安装方案：更改包名后缀，规避已安装不同签名导致的“App 未安装”
        create("portable") {
            initWith(getByName("debug"))
            isMinifyEnabled = false
            signingConfig = signingConfigs.getByName("release")
            applicationIdSuffix = ".portable"
            versionNameSuffix = "-portable"
            matchingFallbacks += listOf("debug")
            // ✅ 真机联调安装包：改为线上 HTTPS，不带端口
            buildConfigField("String", "BACKEND_BASE_URL", "\"https://yiqilishi.com.cn/index.php/api/\"")
            buildConfigField("String", "SOCKET_BASE_URL", "\"https://yiqilishi.com.cn\"")
            buildConfigField("String", "SOCKET_PATH", "\"/socket.io/\"")
            buildConfigField("int", "LOTTERY_DURATION_MS", "9000")
        }
    }
    // 启用 BuildConfig 以支持自定义构建常量（如 LOTTERY_DURATION_MS 等）
    buildFeatures {
        buildConfig = true
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_1_8
        targetCompatibility = JavaVersion.VERSION_1_8
    }
    kotlinOptions {
        jvmTarget = "1.8"
        // 忽略已弃用API的警告，避免编译失败
        freeCompilerArgs += listOf("-Xsuppress-version-warnings")
    }

    // 明确指定主清单文件路径，避免因 AGP/SourceSets 配置导致未合并正确的 Manifest
    sourceSets {
        getByName("main") {
            manifest.srcFile("src/main/AndroidManifest.xml")
        }
        // 移除未被 AGP 识别的自定义 installable 源集，避免构建错误
    }
}

// 为 release 构建添加收尾任务：复制到 app/app/ 目录并重命名
val timestamp: String = System.currentTimeMillis().toString()

// 移除 renameInstallableApk 任务，避免无效任务导致混淆
tasks.register<Copy>("renameInstallableApk") {
    val src = layout.buildDirectory.file("outputs/apk/installable/app-installable.apk")
    from(src)
    into(file("${project.projectDir}/app"))
    rename { "yiqilishi-installable-${timestamp}.apk" }
}

tasks.register<Copy>("renameReleaseApk") {
    val src = layout.buildDirectory.file("outputs/apk/release/app-release.apk")
    from(src)
    into(file("${project.projectDir}/app"))
    rename { "yiqilishi-release-${timestamp}.apk" }
}

// 复制 releaseAlt 生成的 APK 到 app/app 目录并追加时间戳，便于分发
tasks.register<Copy>("renameReleaseAltApk") {
    val src = layout.buildDirectory.file("outputs/apk/releaseAlt/app-releaseAlt.apk")
    from(src)
    into(file("${project.projectDir}/app"))
    rename { "yiqilishi-releaseAlt-${timestamp}.apk" }
}

// 便携安装包重命名复制
tasks.register<Copy>("renamePortableApk") {
    val src = layout.buildDirectory.file("outputs/apk/portable/app-portable.apk")
    from(src)
    into(file("${project.projectDir}/app"))
    rename { "yiqilishi-portable-${timestamp}.apk" }
}

// 某些AGP版本不会生成 assembleInstallable 任务，这里不强行绑定，避免构建失败

// 避免在配置期直接查找任务导致构建失败，改为遍历匹配
tasks.configureEach {
    if (name == "assembleRelease") {
        finalizedBy("renameReleaseApk")
    }
    if (name == "assembleReleaseAlt") {
        finalizedBy("renameReleaseAltApk")
    }
    // 移除对 assembleInstallable 的后置绑定
    // 兼容引入 flavors 后的任务命名（如 assembleLegacy19Portable）
    if (name.endsWith("Portable")) {
        finalizedBy("renamePortableApk")
    }
}

dependencies {

    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.material)
    implementation("androidx.gridlayout:gridlayout:1.0.0")
    implementation("androidx.cardview:cardview:1.0.0")
    implementation("androidx.recyclerview:recyclerview:1.3.2")
    
    // 网络请求相关依赖（按 API 等级分组以兼容 4.4 设备）
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    // 标准 21+ 使用 OkHttp 4.x
    add("standard21Implementation", "com.squareup.okhttp3:okhttp:4.11.0")
    add("standard21Implementation", "com.squareup.okhttp3:logging-interceptor:4.11.0")
    // 兼容 19 使用 OkHttp 3.12.x（最后一个支持 API19 的版本）
    add("legacy19Implementation", "com.squareup.okhttp3:okhttp:3.12.13")
    add("legacy19Implementation", "com.squareup.okhttp3:logging-interceptor:3.12.13")
    implementation("com.google.code.gson:gson:2.10.1")
    
    // WebSocket相关依赖
    implementation("io.socket:socket.io-client:2.1.0") {
        // 解决 Lint DuplicatePlatformClasses：排除与 Android 自带 org.json 冲突的依赖
        exclude(group = "org.json", module = "json")
    }
    
    // 图片加载库
    implementation("com.github.bumptech.glide:glide:4.15.1")
    
    // 二维码生成库
    implementation("com.google.zxing:core:3.5.2")
    implementation("com.journeyapps:zxing-android-embedded:4.3.0")
    
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
}
