/**
 * DMCA Panel — Frontend i18n (zh / en)
 */
(function () {
    'use strict';

    var L = {
        zh: {
            'page.title': 'DMCA 版权侵权举报',
            'page.heading': 'DMCA 版权侵权举报',
            'page.subtitle': '请填写以下信息，我们将在 48 小时内审核处理',

            'nav.brand': 'DMCA Panel',
            'nav.login': '管理员登录',

            'form.label_name': '举报人姓名',
            'form.label_email': '举报人邮箱',
            'form.label_company': '权利人 / 公司名称',
            'form.label_work': '原始作品描述',
            'form.hint_work': '请说明您拥有版权的原始作品名称、类型及相关证明信息',
            'form.label_url': '侵权链接',
            'form.label_hash': 'Info Hash（40 位十六进制）',
            'form.label_desc': '补充说明',

            'affirm.goodfaith': '我善意地相信，上述侵权材料的使用未经版权所有者、其代理人或法律的授权。',
            'affirm.accuracy': '本通知中的信息准确无误。本人愿承担作伪证的法律责任，并声明本人是已声明被侵犯的专有权所有者授权的代表。',
            'affirm.authority': '我了解，故意作出虚假陈述可能需承担法律责任（包括损害赔偿和诉讼费用）。',

            'form.submit': '提交举报',

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
            'error.server': '提交失败，请稍后重试。',

            'success.submit': '举报已提交，我们将在 48 小时内审核处理。',

            'lang.switch': 'English',
        },
        en: {
            'page.title': 'DMCA Copyright Infringement Report',
            'page.heading': 'DMCA Copyright Infringement Report',
            'page.subtitle': 'Please fill in the information below. We will review within 48 hours.',

            'nav.brand': 'DMCA Panel',
            'nav.login': 'Admin Login',

            'form.label_name': 'Reporter Name',
            'form.label_email': 'Reporter Email',
            'form.label_company': 'Rights Holder / Company',
            'form.label_work': 'Original Work Description',
            'form.hint_work': 'Describe your copyrighted original work, including title, type, and proof of ownership',
            'form.label_url': 'Infringing URL',
            'form.label_hash': 'Info Hash (40 hex characters)',
            'form.label_desc': 'Additional Notes',

            'affirm.goodfaith': 'I have a good faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law.',
            'affirm.accuracy': 'The information in this notification is accurate, and under penalty of perjury, I am authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.',
            'affirm.authority': 'I understand that knowingly making a false statement may subject me to liability for damages, including costs and attorneys\' fees.',

            'form.submit': 'Submit Report',

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
            'error.server': 'Submission failed. Please try again later.',

            'success.submit': 'Report submitted. We will review within 48 hours.',

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
                } else if (el.tagName === 'META') {
                    el.content = dict[key];
                } else {
                    el.textContent = dict[key];
                }
            }
        });

        // document title
        if (dict['page.title']) document.title = dict['page.title'];

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
