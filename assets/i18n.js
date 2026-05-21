/**
 * DMCA Panel — Frontend i18n (zh / en)
 */
(function () {
    'use strict';

    var L = {
        zh: {
            // Public page
            'page.title': 'DMCA 版权侵权举报',
            'page.heading': 'DMCA 版权侵权举报',
            'page.subtitle': '请填写以下信息，我们将在 48 小时内审核处理',
            'nav.brand': 'DMCA Panel',
            'nav.login': '管理员登录',

            // Public form labels
            'form.label_name': '举报人姓名',
            'form.label_email': '举报人邮箱',
            'form.label_company': '权利人 / 公司名称',
            'form.label_address': '联系地址',
            'form.label_role': '举报人身份',
            'form.role_owner': '本人即版权所有者',
            'form.role_rep': '本人是版权所有者的授权代表',
            'form.label_work': '原始作品描述',
            'form.hint_work': '请说明您拥有版权的原始作品名称、类型及相关证明信息',
            'form.label_url': '侵权链接',
            'form.label_infringing_location': '侵权内容具体位置',
            'form.hint_infringing_location': '例如：具体 URL 路径、文件名、或 torrent 内文件列表（选填）',
            'form.label_hash': 'Info Hash（40 位十六进制）',
            'form.label_desc': '补充说明',
            'form.label_phone': '联系电话',
            'form.hint_phone': '选填，仅在需要时用于联系',
            'affirm.goodfaith': '我善意地相信，上述侵权材料的使用未经版权所有者、其代理人或法律的授权。',
            'affirm.accuracy': '本通知中的信息准确无误。本人愿承担作伪证的法律责任，并声明本人是已声明被侵犯的专有权所有者授权的代表。',
            'affirm.authority': '我了解，故意作出虚假陈述可能需承担法律责任（包括损害赔偿和诉讼费用）。',
            'form.submit': '提交举报',
            'signature.consent': '本人声明上述信息真实准确，并在此进行电子签名确认。输入姓名即视为正式签名，具有法律效力。',

            // Public errors
            'error.name': '请输入举报人姓名',
            'error.email': '请输入举报人邮箱',
            'error.email_fmt': '邮箱格式不正确',
            'error.work': '请描述您的原始作品',
            'error.hash': 'Info Hash 格式不正确（应为 40 位十六进制字符）',
            'error.affirm_goodfaith': '请确认善意声明',
            'error.affirm_accuracy': '请确认信息准确性声明',
            'error.affirm_authority': '请确认权利人授权声明',
            'error.rate': '提交过于频繁，请稍后再试。',
            'error.csrf': '请求无效，请刷新页面后重试。',
            'error.address': '请输入联系地址',
            'error.role': '请选择举报人身份',
            'error.signature_consent': '请勾选电子签名确认',
            'error.signature_name': '请输入电子签名姓名',
            'error.server': '提交失败，请稍后重试。',
            'success.submit': '举报已提交，我们将在 48 小时内审核处理。',

            // Login page
            'login.title': '管理员登录',
            'login.username': '用户名',
            'login.password': '密码',
            'login.button': '登录',
            'login.back': '← 返回举报页面',

            // Admin sidebar
            'admin.sidebar.reports': '举报列表',
            'admin.sidebar.settings': 'Tracker 设置',
            'admin.sidebar.logout': '退出登录',

            // Admin dashboard
            'admin.heading': 'DMCA 举报管理',
            'admin.view_public': '查看公开页面 →',

            // Tabs
            'admin.tab.all': '全部',
            'admin.tab.pending': '待审核',
            'admin.tab.approved': '已通过',
            'admin.tab.rejected': '已驳回',
            'admin.tab.trash': '回收站',

            // Table headers
            'admin.th.id': 'ID',
            'admin.th.reporter': '举报人',
            'admin.th.company': '公司',
            'admin.th.work': '原始作品',
            'admin.th.hash': 'Info Hash',
            'admin.th.status': '状态',
            'admin.th.date': '提交时间',
            'admin.th.actions': '操作',

            // Action buttons
            'admin.btn.approve': '通过',
            'admin.btn.reject': '驳回',
            'admin.btn.reopen': '重新打开',
            'admin.btn.trash': '删除',
            'admin.btn.restore': '恢复',
            'admin.btn.purge': '永久删除',
            'admin.btn.cancel': '取消',
            'admin.btn.confirm_reject': '确认驳回',

            // Status labels
            'admin.status.pending': '待审核',
            'admin.status.approved': '已通过',
            'admin.status.rejected': '已驳回',
            'admin.status.deleted': '已删除',

            // Reject form
            'admin.reject.label': '驳回理由',
            'admin.reject.placeholder': '可选填写驳回理由...',

            // Search
            'admin.search.placeholder': '搜索 举报人 / 邮箱 / 公司 / Info Hash ...',

            // Empty state
            'admin.empty.title': '暂无数据',
            'admin.empty.desc': '没有符合条件的举报记录',

            // Pagination
            'admin.pagination.prev': '← 上一页',
            'admin.pagination.next': '下一页 →',

            // Modal
            'admin.modal.title': '举报详情',
            'admin.modal.reporter': '举报人',
            'admin.modal.company': '权利人',
            'admin.modal.address': '联系地址',
            'admin.modal.role': '举报人身份',
            'admin.modal.phone': '联系电话',
            'admin.modal.work': '原始作品',
            'admin.modal.url': '侵权链接',
            'admin.modal.location': '侵权具体位置',
            'admin.modal.hash': 'Info Hash',
            'admin.modal.desc': '补充说明',
            'admin.modal.signature': '电子签名',
            'admin.modal.status': '状态',
            'admin.modal.admin_note': '管理员备注',
            'admin.copy': '复制',
            'admin.copied': '已复制',

            // Settings
            'settings.heading': 'Tracker API 设置',
            'settings.intro': '审核通过举报后，系统将先 GET 查询是否已拉黑，再 POST 添加。GET 只读无副作用，POST 会写入 blacklist 文件。',
            'settings.api_label': 'API 地址',
            'settings.api_hint': '两个接口共用同一 URL：GET ?info_hash= 查询，POST 添加',
            'settings.token_label': 'Bearer Token',
            'settings.token_hint': '对应 Rustracker 的 RUSTRACKER_ADMIN_TOKEN',
            'settings.auto_label': '审核通过后自动推送 Info Hash 至 Rustracker 黑名单',
            'settings.auto_hint': '关闭后，审核通过仅变更状态，不调用 Rustracker API',
            'settings.btn_save': '保存设置',
            'settings.btn_test_get': '测试 GET 查询（只读）',
            'settings.btn_test_post': '测试 POST 添加',

            'lang.switch': 'English',
        },
        en: {
            // Public page
            'page.title': 'DMCA Copyright Infringement Report',
            'page.heading': 'DMCA Copyright Infringement Report',
            'page.subtitle': 'Please fill in the information below. We will review within 48 hours.',
            'nav.brand': 'DMCA Panel',
            'nav.login': 'Admin Login',

            // Public form labels
            'form.label_name': 'Reporter Name',
            'form.label_email': 'Reporter Email',
            'form.label_company': 'Rights Holder / Company',
            'form.label_address': 'Contact Address',
            'form.label_role': 'Reporter Role',
            'form.role_owner': 'I am the copyright owner',
            'form.role_rep': 'I am an authorized representative of the copyright owner',
            'form.label_work': 'Original Work Description',
            'form.hint_work': 'Describe your copyrighted original work, including title, type, and proof of ownership',
            'form.label_url': 'Infringing URL',
            'form.label_infringing_location': 'Specific Location of Infringing Content',
            'form.hint_infringing_location': 'e.g. specific URL path, filenames, or list of files within the torrent (optional)',
            'form.label_hash': 'Info Hash (40 hex characters)',
            'form.label_desc': 'Additional Notes',
            'form.label_phone': 'Phone Number',
            'form.hint_phone': 'Optional, only used for contact if needed',
            'affirm.goodfaith': 'I have a good faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law.',
            'affirm.accuracy': 'The information in this notification is accurate, and under penalty of perjury, I am authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.',
            'affirm.authority': 'I understand that knowingly making a false statement may subject me to liability for damages, including costs and attorneys\' fees.',
            'form.submit': 'Submit Report',
            'signature.consent': 'I declare that the above information is true and accurate, and I hereby provide my electronic signature. Typing my name constitutes a formal signature with legal effect.',

            // Public errors
            'error.name': 'Please enter your name',
            'error.email': 'Please enter your email',
            'error.email_fmt': 'Invalid email format',
            'error.work': 'Please describe your original work',
            'error.hash': 'Info Hash must be 40 hexadecimal characters',
            'error.affirm_goodfaith': 'Please confirm the Good Faith statement',
            'error.affirm_accuracy': 'Please confirm the Accuracy statement',
            'error.affirm_authority': 'Please confirm the Authority statement',
            'error.rate': 'Too many submissions. Please try again later.',
            'error.csrf': 'Invalid request. Please refresh the page.',
            'error.address': 'Please enter your contact address',
            'error.role': 'Please select your role',
            'error.signature_consent': 'Please confirm the electronic signature',
            'error.signature_name': 'Please enter your full name as electronic signature',
            'error.server': 'Submission failed. Please try again later.',
            'success.submit': 'Report submitted. We will review within 48 hours.',

            // Login page
            'login.title': 'Admin Login',
            'login.username': 'Username',
            'login.password': 'Password',
            'login.button': 'Log In',
            'login.back': '← Back to Report Page',

            // Admin sidebar
            'admin.sidebar.reports': 'Reports',
            'admin.sidebar.settings': 'Tracker Settings',
            'admin.sidebar.logout': 'Log Out',

            // Admin dashboard
            'admin.heading': 'DMCA Report Management',
            'admin.view_public': 'View Public Page →',

            // Tabs
            'admin.tab.all': 'All',
            'admin.tab.pending': 'Pending',
            'admin.tab.approved': 'Approved',
            'admin.tab.rejected': 'Rejected',
            'admin.tab.trash': 'Trash',

            // Table headers
            'admin.th.id': 'ID',
            'admin.th.reporter': 'Reporter',
            'admin.th.company': 'Company',
            'admin.th.work': 'Original Work',
            'admin.th.hash': 'Info Hash',
            'admin.th.status': 'Status',
            'admin.th.date': 'Date',
            'admin.th.actions': 'Actions',

            // Action buttons
            'admin.btn.approve': 'Approve',
            'admin.btn.reject': 'Reject',
            'admin.btn.reopen': 'Reopen',
            'admin.btn.trash': 'Delete',
            'admin.btn.restore': 'Restore',
            'admin.btn.purge': 'Delete Forever',
            'admin.btn.cancel': 'Cancel',
            'admin.btn.confirm_reject': 'Confirm Reject',

            // Status labels
            'admin.status.pending': 'Pending',
            'admin.status.approved': 'Approved',
            'admin.status.rejected': 'Rejected',
            'admin.status.deleted': 'Deleted',

            // Reject form
            'admin.reject.label': 'Rejection Reason',
            'admin.reject.placeholder': 'Optional reason for rejection...',

            // Search
            'admin.search.placeholder': 'Search reporter / email / company / Info Hash ...',

            // Empty state
            'admin.empty.title': 'No Data',
            'admin.empty.desc': 'No reports match the current filter',

            // Pagination
            'admin.pagination.prev': '← Previous',
            'admin.pagination.next': 'Next →',

            // Modal
            'admin.modal.title': 'Report Detail',
            'admin.modal.reporter': 'Reporter',
            'admin.modal.company': 'Rights Holder',
            'admin.modal.address': 'Contact Address',
            'admin.modal.role': 'Reporter Role',
            'admin.modal.phone': 'Phone',
            'admin.modal.work': 'Original Work',
            'admin.modal.url': 'Infringing URL',
            'admin.modal.location': 'Infringing Location',
            'admin.modal.hash': 'Info Hash',
            'admin.modal.desc': 'Additional Notes',
            'admin.modal.signature': 'Electronic Signature',
            'admin.modal.status': 'Status',
            'admin.modal.admin_note': 'Admin Note',
            'admin.copy': 'Copy',
            'admin.copied': 'Copied',

            // Settings
            'settings.heading': 'Tracker API Settings',
            'settings.intro': 'When a report is approved, the system will first GET query whether the hash is already blacklisted, then POST to add it. GET is read-only; POST writes to the blacklist file.',
            'settings.api_label': 'API URL',
            'settings.api_hint': 'Both endpoints share the same URL: GET ?info_hash= for query, POST for add',
            'settings.token_label': 'Bearer Token',
            'settings.token_hint': 'Corresponds to Rustracker\'s RUSTRACKER_ADMIN_TOKEN',
            'settings.auto_label': 'Automatically push Info Hash to Rustracker blacklist on approval',
            'settings.auto_hint': 'When disabled, approval only changes status without calling the Rustracker API',
            'settings.btn_save': 'Save Settings',
            'settings.btn_test_get': 'Test GET Query (Read-only)',
            'settings.btn_test_post': 'Test POST Add',

            'lang.switch': '中文',
        }
    };

    // Detect language: zh → Chinese, everything else → English
    function detectLang() {
        var stored = localStorage.getItem('dmca-lang');
        if (stored === 'zh' || stored === 'en') return stored;
        var browser = (navigator.language || '').toLowerCase();
        if (browser.indexOf('zh') === 0) return 'zh';
        return 'en';
    }

    var currentLang = detectLang();

    // Apply translations
    function applyLang(lang) {
        currentLang = lang;
        localStorage.setItem('dmca-lang', lang);

        var dict = L[lang];
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            if (dict[key]) {
                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                    el.placeholder = dict[key];
                } else {
                    el.textContent = dict[key];
                }
            }
        });

        // Handle elements with data-i18n-title for title attributes
        document.querySelectorAll('[data-i18n-title]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-title');
            if (dict[key]) el.title = dict[key];
        });

        // document title
        var titleKey = document.title ? null : null;
        var titleEl = document.querySelector('title[data-i18n]');
        if (titleEl && dict[titleEl.getAttribute('data-i18n')]) {
            document.title = dict[titleEl.getAttribute('data-i18n')];
        }

        // Update switch label
        var sw = document.getElementById('lang-switch');
        if (sw) sw.textContent = dict['lang.switch'];

        // Update html lang
        document.documentElement.lang = lang;
    }

    // Toggle language
    function toggleLang() {
        applyLang(currentLang === 'zh' ? 'en' : 'zh');
    }

    // Init
    document.addEventListener('DOMContentLoaded', function () {
        applyLang(currentLang);

        var sw = document.getElementById('lang-switch');
        if (sw) sw.addEventListener('click', toggleLang);
    });

    window.__i18n = {
        t: function (key) { return L[currentLang][key] || key; },
        lang: function () { return currentLang; },
        toggle: toggleLang,
    };
})();
