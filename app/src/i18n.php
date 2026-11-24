<?php
/**
 * Language state and interface strings (navigation chrome, form labels, validation, accessibility).
 *
 * Page copy lives in the editable Website Content store (content.php). These are fixed interface
 * strings; each key has an English and an Egyptian Arabic value. Values may contain %s placeholders.
 */

declare(strict_types=1);

const VN_LANGS = array( 'en', 'ar' );

function vn_lang(): string {
	$lang = $GLOBALS['vn_current_lang'] ?? 'en';
	return in_array( $lang, VN_LANGS, true ) ? $lang : 'en';
}

function vn_set_lang( string $lang ): void {
	$GLOBALS['vn_current_lang'] = in_array( $lang, VN_LANGS, true ) ? $lang : 'en';
}

function vn_is_rtl( ?string $lang = null ): bool {
	return 'ar' === ( $lang ?? vn_lang() );
}

function vn_other_lang( ?string $lang = null ): string {
	return 'ar' === ( $lang ?? vn_lang() ) ? 'en' : 'ar';
}

function vn_strings(): array {
	static $strings = null;
	if ( null !== $strings ) {
		return $strings;
	}
	$strings = array(
		// Chrome / accessibility.
		'skip_link'            => array( 'Skip to main content', 'انتقل للمحتوى الأساسي' ),
		'main_nav'             => array( 'Main navigation', 'القائمة الرئيسية' ),
		'footer_nav'           => array( 'Footer navigation', 'روابط الفوتر' ),
		'open_menu'            => array( 'Open menu', 'افتح القائمة' ),
		'close_menu'           => array( 'Close menu', 'اقفل القائمة' ),
		'lang_switch_label'    => array( 'Choose language', 'اختار اللغة' ),
		'lang_switch_to'       => array( 'عربي', 'EN' ),
		'lang_switch_to_full'  => array( 'اقرأ الصفحة بالعربي', 'Read this page in English' ),
		'lang_unavailable'     => array( 'This page is not available in Arabic yet. You will go to the Arabic section page.', 'الصفحة دي لسه مش متاحة بالإنجليزي.' ),
		'home_label'           => array( 'ViralNest home', 'الصفحة الرئيسية لـ ViralNest' ),
		'social_links'         => array( 'ViralNest on social media', 'ViralNest على السوشيال ميديا' ),
		'opens_new_tab'        => array( '(opens in a new tab)', '(بيفتح في تاب جديدة)' ),
		'whatsapp_chat'        => array( 'Chat with us on WhatsApp', 'كلّمنا على واتساب' ),
		'privacy_settings'     => array( 'Privacy settings', 'إعدادات الخصوصية' ),
		'all_rights'           => array( 'All rights reserved.', 'جميع الحقوق محفوظة.' ),
		'read_more'            => array( 'Learn more', 'اعرف أكتر' ),
		'view_project'         => array( 'View project', 'شوف المشروع' ),
		'view_service'         => array( 'Explore service', 'استكشف الخدمة' ),
		'back_to_services'     => array( 'All services', 'كل الخدمات' ),
		'back_to_work'         => array( 'All work', 'كل الشغل' ),
		'facts_label'          => array( 'ViralNest at a glance', 'ViralNest في أرقام' ),
		'fact_categories'      => array( 'Service categories', 'مجالات خدمة' ),
		'fact_services'        => array( 'Services you can pick from', 'خدمة تقدر تختار منها' ),
		'fact_models'          => array( 'Ways to work with us', 'طرق للشغل معانا' ),
		'fact_steps'           => array( 'Steps from first call to growth', 'خطوات من أول مكالمة لحد النمو' ),
		'marquee_label'        => array( 'What we do', 'بنعمل إيه' ),
		'back_to_top'          => array( 'Back to top', 'ارجع لفوق' ),
		'footer_big'           => array( 'Let’s make your brand spread.', 'يلا نخلّي براندك ينتشر.' ),
		'cursor_view'          => array( 'View', 'شوف' ),
		'breadcrumb'           => array( 'Breadcrumb', 'مسار الصفحة' ),
		'included'             => array( 'What can be included', 'ممكن يشمل إيه' ),
		'sub_services'         => array( 'Services you can select', 'خدمات تقدر تختارها' ),
		'pricing'              => array( 'Pricing', 'الأسعار' ),
		'price_from'           => array( 'Starting from %s', 'تبدأ من %s' ),
		'price_fixed'          => array( '%s', '%s' ),
		'price_default'        => array( 'Every proposal is based on your scope, timeline and goals. Tell us what you need and we will send a tailored quote.', 'كل عرض سعر بيتحدد حسب نطاق الشغل والمدة والأهداف. قولنا محتاج إيه وهنبعتلك عرض مناسب ليك.' ),
		'ad_spend_note'        => array( 'Ad spend is paid separately from our management fees.', 'ميزانية الإعلانات منفصلة عن أتعاب الإدارة بتاعتنا.' ),
		'request_service'      => array( 'Request a quote for this service', 'اطلب عرض سعر للخدمة دي' ),
		'request_similar'      => array( 'Request a similar project', 'اطلب مشروع شبه ده' ),
		// Portfolio.
		'filter_work'          => array( 'Filter projects', 'فلتر المشاريع' ),
		'filter_all'           => array( 'All', 'الكل' ),
		'filter_by_service'    => array( 'By service', 'حسب الخدمة' ),
		'filter_by_industry'   => array( 'By industry', 'حسب المجال' ),
		'filter_results'       => array( '%s projects shown', 'عدد المشاريع المعروضة: %s' ),
		'no_projects'          => array( 'Case studies are being prepared and will be published here once approved.', 'دراسات الحالة بتتجهز وهتتنشر هنا أول ما تتعتمد.' ),
		'no_projects_filter'   => array( 'No projects match this filter yet.', 'مفيش مشاريع مطابقة للفلتر ده لسه.' ),
		'project_client'       => array( 'Client', 'العميل' ),
		'project_industry'     => array( 'Industry', 'المجال' ),
		'project_scope'        => array( 'Scope', 'نطاق الشغل' ),
		'project_services'     => array( 'Services delivered', 'الخدمات اللي اتقدمت' ),
		'project_outcomes'     => array( 'Outcomes', 'النتايج' ),
		'project_gallery'      => array( 'Project gallery', 'صور المشروع' ),
		'overview'             => array( 'Overview', 'نظرة عامة' ),
		// Contact.
		'contact_whatsapp'     => array( 'WhatsApp', 'واتساب' ),
		'contact_email'        => array( 'Email', 'الإيميل' ),
		'contact_phone'        => array( 'Phone', 'التليفون' ),
		'contact_social'       => array( 'Follow ViralNest', 'تابع ViralNest' ),
		'contact_none'         => array( 'The fastest way to reach us is the quote form. We reply to every request.', 'أسرع طريقة توصلنا بيها هي فورم طلب عرض السعر، وبنرد على كل طلب.' ),
		// Quote form.
		'form_title'           => array( 'Tell us what you need', 'قولنا محتاج إيه' ),
		'form_services'        => array( 'Which services do you need?', 'محتاج أنهي خدمات؟' ),
		'form_services_hint'   => array( 'Select one or more. You can pick a whole service or specific parts of it.', 'اختار خدمة أو أكتر. تقدر تختار الخدمة كلها أو أجزاء معينة منها.' ),
		'form_whole_service'   => array( 'Full %s', '%s بالكامل' ),
		'form_selected_count'  => array( '%s selected', 'تم اختيار %s' ),
		'form_email'           => array( 'Email address', 'الإيميل' ),
		'form_phone'           => array( 'Phone number', 'رقم التليفون' ),
		'form_country'         => array( 'Country code', 'كود الدولة' ),
		'form_phone_hint'      => array( 'We may call or message you on this number about your request.', 'ممكن نكلمك أو نبعتلك رسالة على الرقم ده بخصوص طلبك.' ),
		'form_name'            => array( 'Your name', 'اسمك' ),
		'form_company'         => array( 'Business or brand name', 'اسم البيزنس أو البراند' ),
		'form_website'         => array( 'Website or social page', 'الموقع أو صفحة السوشيال' ),
		'form_message'         => array( 'Short project description', 'وصف مختصر للمشروع' ),
		'form_message_hint'    => array( 'Goals, timeline, markets, or anything that helps us prepare. Max 2,000 characters.', 'الأهداف، المدة، الأسواق، أو أي حاجة تساعدنا نجهّز. بحد أقصى ٢٠٠٠ حرف.' ),
		'form_optional'        => array( 'Optional', 'اختياري' ),
		'form_required'        => array( 'Required', 'مطلوب' ),
		'form_submit'          => array( 'Send request', 'ابعت الطلب' ),
		'form_sending'         => array( 'Sending…', 'بنبعت…' ),
		'form_privacy'         => array( 'We use your details only to respond to this request. See our <a href="%s">privacy policy</a>.', 'بنستخدم بياناتك بس عشان نرد على الطلب ده. اقرأ <a href="%s">سياسة الخصوصية</a>.' ),
		'form_success_title'   => array( 'Request received. Thank you!', 'وصلنا طلبك. شكرًا!' ),
		'form_success_text'    => array( 'Your reference is %s. Our team will review your request and get back to you soon.', 'رقم طلبك %s. فريقنا هيراجع الطلب ويرجعلك قريب.' ),
		'form_duplicate_text'  => array( 'We already received this request (reference %s), so we did not create a duplicate. We will be in touch soon.', 'الطلب ده وصلنا قبل كده (رقم %s)، فمعملناش نسخة مكررة. هنتواصل معاك قريب.' ),
		'form_new_request'     => array( 'Send another request', 'ابعت طلب تاني' ),
		'form_error_summary'   => array( 'Please fix the highlighted fields.', 'من فضلك صحّح الخانات المحددة.' ),
		'form_network_error'   => array( 'We could not reach the server. Your answers are still here. Please check your connection and try again.', 'مقدرناش نوصل للسيرفر. إجاباتك لسه موجودة. اتأكد من النت وجرّب تاني.' ),
		'form_rate_limited'    => array( 'Too many requests from this connection. Please wait a few minutes and try again.', 'في طلبات كتير من نفس الاتصال. استنى كام دقيقة وجرّب تاني.' ),
		'form_server_error'    => array( 'Something went wrong on our side and your request was not saved. Please try again.', 'حصلت مشكلة عندنا والطلب متحفظش. جرّب تاني من فضلك.' ),
		'form_services_changed' => array( 'Some selected services are no longer available. We refreshed the list. Please review your selection.', 'في خدمات اخترتها مبقتش متاحة. حدّثنا القايمة، راجع اختيارك من فضلك.' ),
		'form_no_services'     => array( 'Our service list is being updated. Please contact us directly in the meantime.', 'قايمة الخدمات بتتحدث دلوقتي. تواصل معانا مباشرة لحد ما تخلص.' ),
		'err_services'         => array( 'Select at least one service.', 'اختار خدمة واحدة على الأقل.' ),
		'err_services_invalid' => array( 'One or more selected services are no longer available.', 'خدمة أو أكتر من اللي اخترتها مبقتش متاحة.' ),
		'err_email_required'   => array( 'Enter your email address.', 'اكتب الإيميل بتاعك.' ),
		'err_email_invalid'    => array( 'Enter a valid email address, like name@example.com.', 'اكتب إيميل صحيح، زي name@example.com.' ),
		'err_phone_required'   => array( 'Enter your phone number.', 'اكتب رقم التليفون.' ),
		'err_phone_invalid'    => array( 'Enter a valid phone number for the selected country.', 'اكتب رقم صحيح للدولة اللي اخترتها.' ),
		'err_country'          => array( 'Choose a country code.', 'اختار كود الدولة.' ),
		'err_website'          => array( 'Enter a valid web address, like example.com.', 'اكتب عنوان موقع صحيح، زي example.com.' ),
		'err_too_long'         => array( 'This is too long. Maximum %s characters.', 'ده طويل زيادة. الحد الأقصى %s حرف.' ),
		// Consent.
		'consent_title'        => array( 'Your privacy', 'خصوصيتك' ),
		'consent_text'         => array( 'We use first-party analytics to understand which pages help visitors. Optional analytics run only if you allow them. Quote requests work either way.', 'بنستخدم تحليلات خاصة بينا عشان نفهم أنهي صفحات بتفيد الزوار. التحليلات الاختيارية بتشتغل بس لو وافقت. وطلب عرض السعر شغال في الحالتين.' ),
		'consent_text_notice'  => array( 'We use privacy-friendly first-party analytics with no advertising cookies. You can turn it off any time. Optional third-party analytics run only if you allow them.', 'بنستخدم تحليلات خاصة بينا من غير كوكيز إعلانية، وتقدر تقفلها في أي وقت. التحليلات الخارجية الاختيارية بتشتغل بس لو وافقت.' ),
		'consent_accept'       => array( 'Allow analytics', 'وافق على التحليلات' ),
		'consent_decline'      => array( 'Essential only', 'الأساسي بس' ),
		'consent_more'         => array( 'Privacy policy', 'سياسة الخصوصية' ),
		// Errors / fallbacks.
		'not_found_title'      => array( 'This page went viral somewhere else.', 'الصفحة دي انتشرت في حتة تانية.' ),
		'untranslated_title'   => array( 'This page is not available in Arabic yet', 'الصفحة دي لسه مش متاحة بالعربي' ),
		'untranslated_text'    => array( 'You can read the English version, or browse the Arabic pages.', 'تقدر تقرا النسخة الإنجليزي، أو تتصفح الصفحات العربي.' ),
		'read_english'         => array( 'Read in English', 'اقرأ بالإنجليزي' ),
		'go_home'              => array( 'Go to the homepage', 'روح للصفحة الرئيسية' ),
		'preview_banner'       => array( 'Preview of unpublished changes. Visitors do not see this yet.', 'دي معاينة لتعديلات لسه متنشرتش. الزوار مش شايفينها.' ),
		'step'                 => array( 'Step %s', 'خطوة %s' ),
		// Case studies.
		'case_challenge'       => array( 'The challenge', 'التحدي' ),
		'case_approach'        => array( 'Our approach', 'عملنا إيه' ),
		'case_deliverables'    => array( 'What we delivered', 'سلّمنا إيه' ),
		'case_links'           => array( 'See it live', 'شوفه على الطبيعة' ),
		'case_year'            => array( 'Year', 'السنة' ),
		'case_next'            => array( 'Next case study', 'المشروع اللي بعده' ),
		'case_count'           => array( '%s case studies', '%s دراسة حالة' ),
		'case_featured'        => array( 'Featured', 'مميز' ),
		// Careers form.
		'apply_name'           => array( 'Full name', 'الاسم بالكامل' ),
		'apply_email'          => array( 'Email address', 'الإيميل' ),
		'apply_phone'          => array( 'Phone or WhatsApp number', 'رقم التليفون أو الواتساب' ),
		'apply_specialty'      => array( 'Your main specialty', 'تخصصك الأساسي' ),
		'apply_specialty_other'=> array( 'Tell us your specialty', 'اكتب تخصصك' ),
		'apply_location'       => array( 'City and country', 'المدينة والدولة' ),
		'apply_experience'     => array( 'Years of experience', 'سنين الخبرة' ),
		'apply_links'          => array( 'Portfolio links', 'لينكات البورتفوليو' ),
		'apply_links_hint'     => array( 'Behance, Dribbble, Google Drive, YouTube or your website. One link per line, up to 5. Add links, a portfolio file, or both.', 'Behance أو Dribbble أو Google Drive أو YouTube أو موقعك. لينك في كل سطر، لحد ٥ لينكات. ضيف لينكات أو ملف بورتفوليو أو الاتنين.' ),
		'apply_about'          => array( 'How can you help us? Describe the services you provide', 'تقدر تساعدنا إزاي؟ اوصف الخدمات اللي بتقدمها' ),
		'apply_about_hint'     => array( 'The services you offer, brands or industries you’ve worked with, the tools you use and what you’re best at. Max 2,000 characters.', 'الخدمات اللي بتقدمها، والبراندات أو المجالات اللي اشتغلت فيها، والأدوات اللي بتستخدمها، وأكتر حاجة شاطر فيها. بحد أقصى ٢٠٠٠ حرف.' ),
		'apply_step_personal'  => array( 'Personal information', 'البيانات الشخصية' ),
		'apply_step_contact'   => array( 'Contact details and social accounts', 'طرق التواصل وحسابات السوشيال' ),
		'apply_step_work'      => array( 'Your work', 'شغلك' ),
		'apply_step_rate'      => array( 'Your rate', 'سعرك' ),
		'apply_step_portfolio' => array( 'Portfolio and CV', 'البورتفوليو والسي ڤي' ),
		'apply_contact_pref'   => array( 'Best way to reach you', 'أنسب طريقة نتواصل بيها معاك' ),
		'pref_whatsapp'        => array( 'WhatsApp', 'واتساب' ),
		'pref_phone'           => array( 'Phone call', 'مكالمة' ),
		'pref_email'           => array( 'Email', 'إيميل' ),
		'apply_socials'        => array( 'Social media accounts', 'حسابات السوشيال ميديا' ),
		'apply_socials_hint'   => array( 'Type your username (like @yourname) or paste the profile link. Fill in only the ones you use.', 'اكتب اليوزرنيم (زي ‎@yourname) أو حط لينك البروفايل. املا اللي بتستخدمه بس.' ),
		'apply_rate_type'      => array( 'How do you charge?', 'بتحاسب إزاي؟' ),
		'rate_hour'            => array( 'Per hour', 'بالساعة' ),
		'rate_project'         => array( 'Per project', 'بالمشروع' ),
		'rate_per_hour'        => array( 'per hour', 'في الساعة' ),
		'rate_per_project'     => array( 'per project', 'للمشروع' ),
		'apply_rate_amount'    => array( 'Your price', 'سعرك' ),
		'apply_rate_amount_hint' => array( 'Your usual price for one hour, or for a typical project. Numbers only.', 'سعرك المعتاد للساعة، أو لمشروع عادي. أرقام بس.' ),
		'apply_rate_currency'  => array( 'Currency', 'العملة' ),
		'apply_pf'             => array( 'Portfolio file', 'ملف البورتفوليو' ),
		'apply_pf_hint'        => array( 'PDF, JPG, PNG or WebP, up to 8 MB.', 'PDF أو JPG أو PNG أو WebP، لحد ٨ ميجا.' ),
		'apply_pf_choose'      => array( 'Choose a file or drop it here', 'اختار ملف أو اسحبه هنا' ),
		'apply_rate'           => array( 'Rate expectations', 'السعر المتوقع' ),
		'apply_rate_hint'      => array( 'For example per project, per hour or per month, and the currency.', 'مثلًا للمشروع أو بالساعة أو بالشهر، والعملة.' ),
		'apply_languages'      => array( 'Languages you work in', 'اللغات اللي بتشتغل بيها' ),
		'apply_availability'   => array( 'Availability', 'الوقت المتاح' ),
		'apply_cv'             => array( 'CV', 'السي ڤي' ),
		'apply_cv_hint'        => array( 'PDF only, up to 5 MB.', 'PDF بس، لحد ٥ ميجا.' ),
		'apply_cv_choose'      => array( 'Choose a PDF file or drop it here', 'اختار ملف PDF أو اسحبه هنا' ),
		'apply_submit'         => array( 'Send application', 'ابعت الطلب' ),
		'apply_privacy'        => array( 'We use your details only to review your application and contact you about projects. See our <a href="%s">privacy policy</a>.', 'بنستخدم بياناتك بس عشان نراجع طلبك ونكلمك بخصوص المشاريع. اقرأ <a href="%s">سياسة الخصوصية</a>.' ),
		'apply_success_ref'    => array( 'Your reference is %s.', 'رقم طلبك %s.' ),
		'apply_duplicate'      => array( 'We already have your application (reference %s). We’ll be in touch when a project fits.', 'طلبك وصلنا قبل كده (رقم %s). هنتواصل معاك لما ييجي مشروع مناسب.' ),
		'apply_choose'         => array( 'Choose…', 'اختار…' ),
		'spec_designer'        => array( 'Graphic designer', 'جرافيك ديزاينر' ),
		'spec_video'           => array( 'Video editor / motion', 'مونتير / موشن' ),
		'spec_creator'         => array( 'Content creator', 'صانع محتوى' ),
		'spec_copywriter'      => array( 'Copywriter', 'كوبي رايتر' ),
		'spec_seo'             => array( 'SEO specialist', 'متخصص SEO' ),
		'spec_developer'       => array( 'Web developer', 'مطور مواقع' ),
		'spec_media_buyer'     => array( 'Media buyer', 'ميديا باير' ),
		'spec_social'          => array( 'Social media manager', 'سوشيال ميديا مانجر' ),
		'spec_photographer'    => array( 'Photographer / videographer', 'مصور فوتو / فيديو' ),
		'spec_other'           => array( 'Other', 'تخصص تاني' ),
		'exp_0'                => array( 'Less than 1 year', 'أقل من سنة' ),
		'exp_1'                => array( '1–2 years', '١–٢ سنة' ),
		'exp_3'                => array( '3–5 years', '٣–٥ سنين' ),
		'exp_6'                => array( 'More than 5 years', 'أكتر من ٥ سنين' ),
		'lang_ar'              => array( 'Arabic', 'عربي' ),
		'lang_en'              => array( 'English', 'إنجليزي' ),
		'lang_fr'              => array( 'French', 'فرنساوي' ),
		'lang_other'           => array( 'Other', 'لغة تانية' ),
		'avail_full'           => array( 'Full-time hours', 'دوام كامل' ),
		'avail_part'           => array( 'Part-time', 'دوام جزئي' ),
		'avail_project'        => array( 'Project by project', 'على حسب المشروع' ),
		'avail_weekends'       => array( 'Evenings and weekends', 'بالليل والويك إند' ),
		'err_name_required'    => array( 'Enter your name.', 'اكتب اسمك.' ),
		'err_specialty'        => array( 'Choose your specialty.', 'اختار تخصصك.' ),
		'err_about_required'   => array( 'Tell us how you can help and the services you provide.', 'قولنا تقدر تساعدنا إزاي والخدمات اللي بتقدمها.' ),
		'err_social_invalid'   => array( 'Enter a username like @yourname or a full profile link.', 'اكتب يوزرنيم زي ‎@yourname أو لينك بروفايل كامل.' ),
		'err_rate_type'        => array( 'Choose per hour or per project.', 'اختار بالساعة أو بالمشروع.' ),
		'err_rate_amount_required' => array( 'Enter your price.', 'اكتب سعرك.' ),
		'err_rate_amount_invalid'  => array( 'Enter the price as a number, like 500.', 'اكتب السعر رقم، زي 500.' ),
		'err_rate_currency'    => array( 'Choose a currency.', 'اختار العملة.' ),
		'err_portfolio_required' => array( 'Add at least one link to your work, or attach a portfolio file.', 'ضيف لينك واحد على الأقل لشغلك، أو ارفع ملف بورتفوليو.' ),
		'err_pf_type'          => array( 'The portfolio file must be a PDF, JPG, PNG or WebP.', 'ملف البورتفوليو لازم يكون PDF أو JPG أو PNG أو WebP.' ),
		'err_pf_size'          => array( 'The portfolio file must be 8 MB or smaller.', 'ملف البورتفوليو لازم يكون ٨ ميجا أو أقل.' ),
		'err_pf_upload'        => array( 'The portfolio file did not upload. Try again.', 'ملف البورتفوليو ماترفعش. جرّب تاني.' ),
		'err_files_too_big'    => array( 'The files are too large together. Keep the CV under 5 MB and the portfolio file under 8 MB.', 'الملفات كبيرة مع بعض. خلّي السي ڤي أقل من ٥ ميجا وملف البورتفوليو أقل من ٨ ميجا.' ),
		'err_links_required'   => array( 'Add at least one link to your work.', 'ضيف لينك واحد على الأقل لشغلك.' ),
		'err_links_invalid'    => array( 'Each line must be a full web address, like https://behance.net/you.', 'كل سطر لازم يكون لينك كامل، زي https://behance.net/you.' ),
		'err_cv_type'          => array( 'The file must be a PDF.', 'الملف لازم يكون PDF.' ),
		'err_cv_size'          => array( 'The file must be 5 MB or smaller.', 'الملف لازم يكون ٥ ميجا أو أقل.' ),
		'err_cv_upload'        => array( 'The file did not upload. Try again or skip it.', 'الملف ماترفعش. جرّب تاني أو كمّل من غيره.' ),
		'err_availability'     => array( 'Choose your availability.', 'اختار الوقت المتاح ليك.' ),
		// Site chrome additions.
		'menu'                 => array( 'Menu', 'القائمة' ),
		'explore'              => array( 'Explore', 'استكشف' ),
		'company'              => array( 'Company', 'الشركة' ),
		'get_in_touch'         => array( 'Get in touch', 'تواصل معانا' ),
		'scroll'               => array( 'Scroll', 'انزل' ),
		'eyebrow_problem'      => array( 'The choice', 'الاختيار' ),
		'eyebrow_value'        => array( 'Why ViralNest', 'ليه ViralNest' ),
		'eyebrow_services'     => array( 'What we do', 'بنعمل إيه' ),
		'eyebrow_models'       => array( 'Ways to work', 'طرق الشغل' ),
		'eyebrow_process'      => array( 'How we work', 'بنشتغل إزاي' ),
		'eyebrow_audience'     => array( 'Who we work with', 'بنشتغل مع مين' ),
		'eyebrow_work'         => array( 'Selected work', 'من شغلنا' ),
		'eyebrow_story'        => array( 'Our story', 'حكايتنا' ),
		'eyebrow_contact'      => array( 'Contact', 'تواصل' ),
		'industries'           => array( 'Industries', 'المجالات' ),
		'markets'              => array( 'Markets', 'الأسواق' ),
		'apply_now'            => array( 'Apply now', 'قدّم دلوقتي' ),
		'see_all_work'         => array( 'See all work', 'شوف كل الشغل' ),
		'see_all_services'     => array( 'See all services', 'شوف كل الخدمات' ),
		'on_this_page'         => array( 'On this page', 'في الصفحة دي' ),
		'related_work'         => array( 'Related work', 'شغل مرتبط' ),
		'quote_cta_text'       => array( 'Pick this service on its own or combine it with others. You’ll get a written proposal based on your scope.', 'اختار الخدمة دي لوحدها أو مع خدمات تانية، وهتاخد عرض مكتوب على حسب نطاق شغلك.' ),
		'service_steps'        => array( 'How we deliver it', 'بننفذها إزاي' ),
		'compare_best'         => array( 'Best of both', 'أحسن ما في الاتنين' ),
		'channels'             => array( 'Channels', 'قنوات التواصل' ),
		'response_note'        => array( 'We reply to every request.', 'بنرد على كل طلب.' ),
		'all_services_count'   => array( '%s services', '%s خدمة' ),
	);
	return $strings;
}

function vn_t( string $key, ...$args ): string {
	$strings = vn_strings();
	$value   = $strings[ $key ][ 'ar' === vn_lang() ? 1 : 0 ] ?? $key;
	return $args ? vsprintf( $value, $args ) : $value;
}

function vn_t_lang( string $lang, string $key, ...$args ): string {
	$prev = vn_lang();
	vn_set_lang( $lang );
	$out = vn_t( $key, ...$args );
	vn_set_lang( $prev );
	return $out;
}

/** Western digits in both languages (consistent with phone numbers and prices). */
function vn_number( $n ): string {
	return number_format( (float) $n, 0, '.', ',' );
}
