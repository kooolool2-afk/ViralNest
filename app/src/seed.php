<?php
/**
 * Starter content, created once by the first-run setup.
 *
 * - The 8 service categories and their sub-services from Part 04 of the ViralNest Master Brief v2.0,
 *   published in English and Egyptian Arabic. A focused subset is enabled in the quote form.
 * - Portfolio drafts for the projects named in the brief: unpublished, client not approved, no
 *   results. Nothing here invents results, testimonials, prices or contact details.
 * - Brand images from the supplied logo (app/data/brand) are imported to the media library.
 */

declare(strict_types=1);

function vn_starter_services(): array {
	// Child: [ slug, status, [ EN title, EN summary ], [ AR title, AR summary ], show in quote form (1/0) ].
	return array(
		array(
			'slug' => 'marketing-strategy-research', 'icon' => 'strategy',
			'en'   => array( 'Marketing Strategy & Research', 'Understand your market, audience and competitors, then get a strategy built on what you learn.', 'Strong marketing starts with research. We study your market, competitors and audience, look at how your customers behave, and define your positioning. Then we turn it into a marketing and growth strategy with clear objectives, planned campaigns and a way to evaluate performance.' ),
			'ar'   => array( 'استراتيجية التسويق والأبحاث', 'افهم السوق والجمهور والمنافسين، وخد استراتيجية مبنية على اللي اتعلمته.', 'التسويق القوي بيبدأ بالبحث. بندرس السوق والمنافسين والجمهور، وبنفهم سلوك عملائك، وبنحدد تموضع البراند. وبعدين بنحوّل ده لاستراتيجية تسويق ونمو بأهداف واضحة، وحملات متخططة، وطريقة لتقييم الأداء.' ),
			'children' => array(
				array( 'market-research', 'publish', array( 'Market research', 'Size, trends and opportunities in the markets you sell in.' ), array( 'أبحاث السوق', 'حجم السوق والاتجاهات والفرص في الأسواق اللي بتبيع فيها.' ), 1 ),
				array( 'competitor-analysis', 'publish', array( 'Competitor analysis', 'What competitors do well, where they are weak and where you can stand out.' ), array( 'تحليل المنافسين', 'المنافسين بيعملوا إيه كويس، وفين نقط ضعفهم، وتقدر تتميز فين.' ), 1 ),
				array( 'audience-research', 'publish', array( 'Audience and customer behavior research', 'Who your customers are, what they need and how they decide.' ), array( 'أبحاث الجمهور وسلوك العملاء', 'مين عملاءك، ومحتاجين إيه، وبياخدوا قرارهم إزاي.' ), 0 ),
				array( 'brand-positioning-strategy', 'publish', array( 'Brand positioning', 'A clear place for your brand in your customers’ minds.' ), array( 'تموضع البراند', 'مكان واضح للبراند بتاعك في دماغ عملائك.' ), 0 ),
				array( 'marketing-growth-strategy', 'publish', array( 'Marketing and growth strategy', 'Objectives, channels and priorities in one practical plan.' ), array( 'استراتيجية التسويق والنمو', 'الأهداف والقنوات والأولويات في خطة واحدة عملية.' ), 1 ),
				array( 'campaign-planning', 'publish', array( 'Campaign planning', 'Campaigns mapped to goals, audiences and timelines.' ), array( 'تخطيط الحملات', 'حملات مربوطة بالأهداف والجمهور والمواعيد.' ), 0 ),
				array( 'performance-evaluation', 'publish', array( 'Marketing performance evaluation', 'A clear read on what is working and what to change.' ), array( 'تقييم أداء التسويق', 'قراءة واضحة لإيه اللي شغال وإيه اللي محتاج يتغير.' ), 0 ),
			),
		),
		array(
			'slug' => 'social-media-management', 'icon' => 'social',
			'en'   => array( 'Social Media Management', 'Facebook, Instagram and TikTok accounts planned, published and managed by one team.', 'We manage your Facebook, Instagram and TikTok accounts end to end: optimizing your pages, scheduling and publishing content, engaging with your community and analyzing how each platform performs, so your channels stay active, consistent and on brand.' ),
			'ar'   => array( 'إدارة السوشيال ميديا', 'حسابات فيسبوك وإنستجرام وتيك توك بيخططها وينشرها ويديرها فريق واحد.', 'بندير حسابات فيسبوك وإنستجرام وتيك توك من الأول للآخر: بنظبط الصفحات، ونجدول المحتوى وننشره، ونتفاعل مع جمهورك، ونحلل أداء كل منصة، عشان قنواتك تفضل نشيطة ومتناسقة ومعبّرة عن البراند.' ),
			'children' => array(
				array( 'account-management', 'publish', array( 'Account management and publishing', 'Day-to-day management of your Facebook, Instagram and TikTok accounts.' ), array( 'إدارة الحسابات والنشر', 'إدارة يومية لحسابات فيسبوك وإنستجرام وتيك توك.' ), 1 ),
				array( 'content-scheduling', 'publish', array( 'Content scheduling', 'The right post on the right platform at the right time.' ), array( 'جدولة المحتوى', 'البوست الصح على المنصة الصح في الوقت الصح.' ), 0 ),
				array( 'community-management', 'publish', array( 'Community management and engagement', 'Comments and messages answered in your brand voice.' ), array( 'إدارة المجتمع والتفاعل', 'الرد على التعليقات والرسائل بصوت البراند بتاعك.' ), 1 ),
				array( 'page-optimization', 'publish', array( 'Page optimization', 'Profiles and pages set up to look professional and convert.' ), array( 'تحسين الصفحات', 'صفحات وبروفايلات شكلها احترافي ومعمولة عشان تحوّل.' ), 0 ),
				array( 'platform-performance-analysis', 'publish', array( 'Platform performance analysis', 'What each platform delivers and where to focus next.' ), array( 'تحليل أداء المنصات', 'كل منصة بتجيب إيه، ونركّز فين الخطوة الجاية.' ), 0 ),
			),
		),
		array(
			'slug' => 'content-strategy-production', 'icon' => 'content',
			'en'   => array( 'Content Strategy & Production', 'From content pillars and monthly calendars to reels, carousels and copy that sells.', 'We decide what to say, where and why, then produce it. Content pillars and research shape monthly calendars and creative concepts, and our team produces the reels, short-form videos, stories, static posts, carousels, captions and CTAs, then keeps optimizing based on what performs.' ),
			'ar'   => array( 'استراتيجية وإنتاج المحتوى', 'من أعمدة المحتوى والخطط الشهرية، للريلز والكاروسيل والكوبي اللي بيبيع.', 'بنحدد هنقول إيه، وفين، وليه، وبعدين بننتجه. أعمدة المحتوى والبحث بيشكّلوا الخطة الشهرية والأفكار الإبداعية، وفريقنا بينتج الريلز والفيديوهات القصيرة والستوريز والبوستات والكاروسيل والكابشنز والـCTAs، وبنفضل نحسّن على حسب الأداء.' ),
			'children' => array(
				array( 'content-strategy', 'publish', array( 'Content strategy and pillars', 'The themes and goals every piece of content serves.' ), array( 'استراتيجية وأعمدة المحتوى', 'المواضيع والأهداف اللي كل محتوى بيخدمها.' ), 1 ),
				array( 'content-calendars', 'publish', array( 'Monthly content calendars', 'Monthly plans that connect every post to a goal.' ), array( 'خطط المحتوى الشهرية', 'خطط شهرية بتربط كل بوست بهدف.' ), 1 ),
				array( 'content-research-concepts', 'publish', array( 'Content research and creative concepts', 'Ideas grounded in your audience and your market.' ), array( 'بحث المحتوى والأفكار الإبداعية', 'أفكار مبنية على جمهورك والسوق بتاعك.' ), 0 ),
				array( 'short-form-video', 'publish', array( 'Reels and short-form video', 'Reels, TikToks and short videos made for social.' ), array( 'الريلز والفيديوهات القصيرة', 'ريلز وتيك توك وفيديوهات قصيرة معمولة للسوشيال.' ), 1 ),
				array( 'posts-carousels-stories', 'publish', array( 'Static posts, carousels and stories', 'Everyday content formats, produced to one standard.' ), array( 'البوستات والكاروسيل والستوريز', 'أشكال المحتوى اليومية بنفس مستوى الجودة.' ), 0 ),
				array( 'copywriting', 'publish', array( 'Copywriting, captions and CTAs', 'Words that sound like your brand and move people to act.' ), array( 'الكوبي رايتنج والكابشنز والـCTAs', 'كلام بصوت البراند بتاعك وبيحرّك الناس ياخدوا خطوة.' ), 1 ),
				array( 'content-optimization', 'publish', array( 'Content optimization', 'Improving formats and messages based on performance.' ), array( 'تحسين المحتوى', 'تطوير الأشكال والرسائل على حسب الأداء.' ), 0 ),
			),
		),
		array(
			'slug' => 'graphic-design', 'icon' => 'design',
			'en'   => array( 'Graphic Design', 'Social, ad and campaign visuals that keep your brand recognizable everywhere.', 'Social media designs, advertising creatives, campaign visuals and promotional materials, all designed to stay consistent with your brand so your audience recognizes you at a glance.' ),
			'ar'   => array( 'التصميم الجرافيكي', 'تصميمات سوشيال وإعلانات وحملات بتخلّي البراند بتاعك معروف في كل مكان.', 'تصميمات سوشيال ميديا، وكرييتيفز إعلانات، وتصميمات حملات ومواد ترويجية، وكلها متناسقة مع البراند عشان جمهورك يعرفك من أول نظرة.' ),
			'children' => array(
				array( 'social-media-designs', 'publish', array( 'Social media designs', 'Posts, stories and covers designed for your channels.' ), array( 'تصميمات السوشيال ميديا', 'بوستات وستوريز وكوفرات متصممة لقنواتك.' ), 1 ),
				array( 'ad-creatives', 'publish', array( 'Advertising creatives', 'Ad visuals designed to stop the scroll and be tested.' ), array( 'كرييتيفز الإعلانات', 'تصميمات إعلانات بتوقف السكرول وجاهزة للاختبار.' ), 1 ),
				array( 'campaign-promotional-designs', 'publish', array( 'Campaign and promotional designs', 'Visual systems for campaigns, offers and promotional materials.' ), array( 'تصميمات الحملات والمواد الترويجية', 'هوية بصرية للحملات والعروض والمواد الترويجية.' ), 1 ),
				array( 'brand-consistent-visuals', 'publish', array( 'Brand-consistent marketing graphics', 'Marketing visuals that always look like you.' ), array( 'جرافيكس تسويقية متناسقة مع البراند', 'تصميمات تسويقية دايمًا شبهك.' ), 0 ),
			),
		),
		array(
			'slug' => 'media-buying-performance', 'icon' => 'media', 'ad_spend' => 1,
			'en'   => array( 'Media Buying & Performance Marketing', 'Meta Ads on Facebook and Instagram, planned, launched, tested and optimized.', 'We plan your campaign strategy, research and build audiences, set up campaigns and ad sets, allocate budgets, test creatives, retarget warm audiences and set up conversion tracking. Then we keep optimizing, analyzing performance and reporting on results. Advertising budgets are paid by you, separately from our management fees.' ),
			'ar'   => array( 'الميديا باينج والتسويق بالأداء', 'إعلانات ميتا على فيسبوك وإنستجرام، بنخططها ونطلقها ونختبرها ونحسّنها.', 'بنخطط استراتيجية الحملات، وندرس الجمهور ونبنيه، ونعمل الحملات والـAd sets، ونوزّع الميزانية، ونختبر الكرييتيفز، ونعمل ريتارجتنج، ونظبط تتبّع التحويلات. وبعدين بنفضل نحسّن ونحلل الأداء ونرفع تقارير. ميزانية الإعلانات بتدفعها إنت، ومنفصلة عن أتعاب الإدارة.' ),
			'children' => array(
				array( 'meta-ads', 'publish', array( 'Meta Ads (Facebook and Instagram)', 'Campaigns on Facebook and Instagram from setup to optimization.' ), array( 'إعلانات ميتا (فيسبوك وإنستجرام)', 'حملات فيسبوك وإنستجرام من الإعداد للتحسين.' ), 1 ),
				array( 'campaign-strategy-setup', 'publish', array( 'Campaign strategy and setup', 'Objectives, structure, audiences and budgets set up properly.' ), array( 'استراتيجية الحملات والإعداد', 'الأهداف والهيكل والجمهور والميزانية متظبطين صح.' ), 0 ),
				array( 'creative-testing', 'publish', array( 'Creative testing', 'Structured tests to find the ads that work.' ), array( 'اختبار الكرييتيفز', 'اختبارات منظّمة عشان نلاقي الإعلانات اللي بتنجح.' ), 0 ),
				array( 'retargeting', 'publish', array( 'Retargeting', 'Bringing back people who already showed interest.' ), array( 'الريتارجتنج', 'نرجّع الناس اللي أبدوا اهتمام قبل كده.' ), 1 ),
				array( 'conversion-tracking', 'publish', array( 'Conversion tracking', 'Measuring the actions that matter to your business.' ), array( 'تتبّع التحويلات', 'قياس الخطوات اللي تهم البيزنس بتاعك.' ), 1 ),
				array( 'ad-optimization-reporting', 'publish', array( 'Optimization and advertising reports', 'Ongoing optimization and clear performance reports.' ), array( 'التحسين وتقارير الإعلانات', 'تحسين مستمر وتقارير أداء واضحة.' ), 0 ),
			),
		),
		array(
			'slug' => 'branding-rebranding', 'icon' => 'branding',
			'en'   => array( 'Branding & Rebranding', 'Positioning, messaging and identity direction for new brands and brands ready to evolve.', 'Whether you are launching or evolving, we shape how your brand is positioned, what it says and how it looks and communicates. For existing brands, we build rebranding strategies that improve how people perceive you while keeping everything consistent.' ),
			'ar'   => array( 'البراندينج وإعادة البراندينج', 'تموضع ورسائل واتجاه للهوية، للبراندات الجديدة والبراندات اللي محتاجة تتطور.', 'سواء بتطلق براند جديد أو بتطوّر براند موجود، بنشكّل تموضع البراند، وبيقول إيه، وشكله وطريقة تواصله. وللبراندات الموجودة، بنبني استراتيجيات إعادة براندينج بتحسّن صورة البراند عند الناس، مع الحفاظ على التناسق.' ),
			'children' => array(
				array( 'brand-positioning', 'publish', array( 'Brand positioning', 'Where your brand stands and why customers should choose it.' ), array( 'تموضع البراند', 'البراند بتاعك واقف فين، وليه العملاء يختاروه.' ), 1 ),
				array( 'brand-identity-direction', 'publish', array( 'Brand identity direction', 'The visual and verbal direction your brand will follow.' ), array( 'اتجاه هوية البراند', 'الاتجاه البصري والكلامي اللي البراند هيمشي عليه.' ), 1 ),
				array( 'brand-messaging', 'publish', array( 'Brand messaging', 'Key messages that explain your value clearly.' ), array( 'رسائل البراند', 'رسائل أساسية بتوضّح قيمتك بوضوح.' ), 0 ),
				array( 'brand-consistency', 'publish', array( 'Visual consistency and brand communication', 'One recognizable brand across every touchpoint.' ), array( 'التناسق البصري وتواصل البراند', 'براند واحد معروف في كل نقطة تواصل.' ), 0 ),
				array( 'rebranding-strategy', 'publish', array( 'Rebranding strategy', 'Evolving your brand and improving how it is perceived.' ), array( 'استراتيجية إعادة البراندينج', 'تطوير البراند وتحسين صورته عند الناس.' ), 1 ),
			),
		),
		array(
			'slug' => 'seo-website-marketing', 'icon' => 'seo',
			'en'   => array( 'SEO & Website Marketing', 'Help the right people find your website through search and organic growth.', 'We build an SEO strategy that improves your search visibility, optimize your content for search, market your website and monitor search performance, so organic traffic keeps growing over time.' ),
			'ar'   => array( 'الـSEO وتسويق المواقع', 'خلّي الناس الصح توصل لموقعك من البحث والنمو الأورجانيك.', 'بنبني استراتيجية SEO بتحسّن ظهورك في البحث، ونظبط المحتوى للبحث، ونسوّق الموقع بتاعك، ونتابع أداء البحث، عشان الزيارات الأورجانيك تفضل تزيد مع الوقت.' ),
			'children' => array(
				array( 'seo-strategy', 'publish', array( 'SEO strategy and search visibility', 'A plan to rank for the searches your customers make.' ), array( 'استراتيجية SEO والظهور في البحث', 'خطة عشان تظهر في عمليات البحث اللي عملاءك بيعملوها.' ), 1 ),
				array( 'website-marketing', 'publish', array( 'Website marketing', 'Bringing the right visitors to your website.' ), array( 'تسويق المواقع', 'نجيب الزوار الصح لموقعك.' ), 1 ),
				array( 'seo-content-optimization', 'publish', array( 'Content optimization for search', 'Pages and articles written to be found.' ), array( 'تحسين المحتوى للبحث', 'صفحات ومقالات مكتوبة عشان تتلاقي.' ), 0 ),
				array( 'organic-growth-monitoring', 'publish', array( 'Organic growth and search monitoring', 'Tracking rankings, traffic and what to improve next.' ), array( 'النمو الأورجانيك ومتابعة البحث', 'متابعة الترتيب والزيارات وإيه اللي نحسّنه بعد كده.' ), 0 ),
			),
		),
		array(
			'slug' => 'creative-campaigns', 'icon' => 'campaign',
			'en'   => array( 'Creative Campaigns', 'Launches, seasonal moments and promotions, built around one strong idea.', 'We develop creative advertising concepts and run them across content, design and media: product launches, seasonal campaigns like Christmas and Black Friday, promotional and sales campaigns, and brand awareness campaigns.' ),
			'ar'   => array( 'الحملات الإبداعية', 'إطلاقات ومواسم وعروض، مبنية على فكرة واحدة قوية.', 'بنطوّر أفكار إعلانية إبداعية وننفذها في المحتوى والتصميم والإعلانات: إطلاق منتجات، وحملات موسمية زي الكريسماس والبلاك فرايداي، وحملات عروض ومبيعات، وحملات وعي بالبراند.' ),
			'children' => array(
				array( 'product-launches', 'publish', array( 'Product launches', 'Launch campaigns that build awareness from day one.' ), array( 'إطلاق المنتجات', 'حملات إطلاق بتبني الوعي من أول يوم.' ), 1 ),
				array( 'seasonal-campaigns', 'publish', array( 'Seasonal campaigns', 'Christmas, Black Friday and other key seasons.' ), array( 'الحملات الموسمية', 'الكريسماس والبلاك فرايداي وغيرهم من المواسم المهمة.' ), 1 ),
				array( 'promotional-sales-campaigns', 'publish', array( 'Promotional and sales campaigns', 'Offers and promotions designed to drive sales.' ), array( 'حملات العروض والمبيعات', 'عروض وبروموشنز معمولة عشان تزوّد المبيعات.' ), 1 ),
				array( 'brand-awareness-campaigns', 'publish', array( 'Brand awareness campaigns', 'Getting your brand known by the right audience.' ), array( 'حملات الوعي بالبراند', 'نعرّف الجمهور الصح بالبراند بتاعك.' ), 1 ),
				array( 'creative-advertising-concepts', 'publish', array( 'Creative advertising concepts', 'Big ideas that make your ads memorable.' ), array( 'أفكار إعلانية إبداعية', 'أفكار كبيرة بتخلّي إعلاناتك تتفتكر.' ), 0 ),
			),
		),
	);
}

/**
 * Portfolio drafts from the brief (Part 16.3). Unpublished, client not approved, no results.
 * The work listed is what the brief says was discussed; confirm it and get the client's
 * permission before publishing.
 */
function vn_starter_projects(): array {
	$confirm_en = "Draft for review. Confirm the work below was delivered and get the client’s written permission before publishing. Do not add results unless they are documented.\n\n";
	$confirm_ar = "مسودة للمراجعة. اتأكد إن الشغل ده اتنفّذ فعلًا وخد موافقة العميل كتابةً قبل النشر. ومتضيفش نتايج إلا لو موثّقة.\n\n";
	return array(
		array(
			'slug'     => 'maloum',
			'client'   => 'maloum (معلوم)',
			'en'       => array( 'maloum: restaurant social media and content', 'Social media, food content and promotions for a restaurant in Hadayek October, Egypt.', $confirm_en . "maloum is a restaurant in Hadayek October, Egypt.\n\nScope of work: social media management, food photography, UGC, AI videos, monthly content calendars, promotional campaigns, platform management and reporting." ),
			'ar'       => array( 'معلوم: سوشيال ميديا ومحتوى لمطعم', 'سوشيال ميديا ومحتوى أكل وعروض لمطعم في حدائق أكتوبر.', $confirm_ar . "معلوم مطعم في حدائق أكتوبر، مصر.\n\nنطاق الشغل: إدارة السوشيال ميديا، وتصوير أكل، ومحتوى UGC، وفيديوهات بالذكاء الاصطناعي، وخطط محتوى شهرية، وحملات ترويجية، وإدارة المنصات، وتقارير." ),
			'industry' => array( 'Restaurants and food', 'المطاعم والأكل' ),
			'scope'    => array( 'Social media management, food photography, UGC, AI videos, content calendars, promotional campaigns, platform management, reporting', 'إدارة السوشيال ميديا، تصوير أكل، UGC، فيديوهات بالذكاء الاصطناعي، خطط محتوى، حملات ترويجية، إدارة المنصات، تقارير' ),
			'services' => array( 'social-media-management', 'content-calendars', 'short-form-video', 'promotional-sales-campaigns' ),
		),
		array(
			'slug'     => 'rika-homemade',
			'client'   => 'Rika Homemade',
			'en'       => array( 'Rika Homemade: Christmas campaign and Meta Ads', 'Seasonal campaign planning and Meta Ads strategy for a homemade food and chocolate brand.', $confirm_en . "Rika Homemade is a homemade food and chocolate brand.\n\nScope of work: Christmas campaign planning, Meta Ads strategy, audience research, creative advertising and content planning." ),
			'ar'       => array( 'Rika Homemade: حملة الكريسماس وإعلانات ميتا', 'تخطيط حملة موسمية واستراتيجية إعلانات ميتا لبراند أكل وشوكولاتة هوم ميد.', $confirm_ar . "Rika Homemade براند أكل وشوكولاتة هوم ميد.\n\nنطاق الشغل: تخطيط حملة الكريسماس، واستراتيجية إعلانات ميتا، وبحث الجمهور، وإعلانات إبداعية، وتخطيط المحتوى." ),
			'industry' => array( 'Food and e-commerce', 'الأكل والتجارة الإلكترونية' ),
			'scope'    => array( 'Christmas campaign planning, Meta Ads strategy, audience research, creative advertising, content planning', 'تخطيط حملة الكريسماس، استراتيجية إعلانات ميتا، بحث الجمهور، إعلانات إبداعية، تخطيط المحتوى' ),
			'services' => array( 'seasonal-campaigns', 'meta-ads', 'audience-research', 'ad-creatives', 'content-calendars' ),
		),
		array(
			'slug'     => 'king-tut-stone',
			'client'   => 'King Tut Stone',
			'en'       => array( 'King Tut Stone: creative marketing and video concepts', 'Creative marketing and video concepts for a stone business (kingtutstone.com).', $confirm_en . "King Tut Stone is a stone business (kingtutstone.com).\n\nScope of work: creative marketing and video concepts." ),
			'ar'       => array( 'King Tut Stone: تسويق إبداعي وأفكار فيديو', 'تسويق إبداعي وأفكار فيديوهات لبيزنس أحجار (kingtutstone.com).', $confirm_ar . "King Tut Stone بيزنس متخصص في الأحجار (kingtutstone.com).\n\nنطاق الشغل: تسويق إبداعي وأفكار فيديوهات." ),
			'industry' => array( 'Stone and building materials', 'الأحجار ومواد البناء' ),
			'scope'    => array( 'Creative marketing, video concepts', 'تسويق إبداعي، أفكار فيديوهات' ),
			'services' => array( 'creative-advertising-concepts', 'short-form-video' ),
			'links'    => "Website|https://kingtutstone.com",
		),
	);
}


/** Seed everything once (kv "seeded"). */
function vn_seed(): void {
	if ( kv_get( 'seeded' ) ) {
		return;
	}
	$now   = db_now();
	$order = 0;
	foreach ( vn_starter_services() as $svc ) {
		$pid = vn_seed_service( 0, $svc['slug'], 'publish', $svc['en'], $svc['ar'], 1, $order += 10, $svc['icon'], ! empty( $svc['ad_spend'] ) );
		$co  = 0;
		foreach ( $svc['children'] as $child ) {
			vn_seed_service( $pid, $child[0], $child[1], $child[2], $child[3], (int) ( $child[4] ?? 1 ), $co += 10, 'strategy', false );
		}
	}
	vn_seed_projects();

	$s = array();
	foreach ( array(
		'logo_id'     => array( 'viralnest-logo.png', 'ViralNest logo', 'لوجو ViralNest' ),
		'favicon_id'  => array( 'viralnest-icon-512.png', 'ViralNest icon', 'أيقونة ViralNest' ),
		'og_image_id' => array( 'viralnest-social-default.jpg', 'ViralNest logo on a dark background', 'لوجو ViralNest على خلفية داكنة' ),
	) as $key => $img ) {
		$file = VN_APP . '/data/brand/' . $img[0];
		if ( is_file( $file ) ) {
			$r = vn_media_import( $file, $img[0], 0, true );
			if ( $r['ok'] ) {
				db_update( 'vn_media', array( 'alt_en' => $img[1], 'alt_ar' => $img[2] ), array( 'id' => $r['id'] ) );
				$s[ $key ] = $r['id'];
			}
		}
	}
	if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
		$s['canonical_host'] = preg_replace( '/:\d+$/', '', strtolower( (string) $_SERVER['HTTP_HOST'] ) );
	}
	vn_save_settings( $s );
	if ( null === kv_get( 'content' ) ) {
		kv_set( 'content', vn_default_content() );
	}
	kv_set( 'seeded', gmdate( 'c' ) );
	vn_catalog_flush();
}

function vn_seed_service( int $parent, string $slug, string $status, array $en, array $ar, int $quote, int $pos, string $icon, bool $ad_spend ): int {
	$id = (int) db_val( 'SELECT id FROM vn_services WHERE slug = ?', array( $slug ) );
	if ( $id ) {
		return $id;
	}
	return db_insert(
		'vn_services',
		array(
			'parent_id'     => $parent,
			'slug'          => $slug,
			'position'      => $pos,
			'status'        => $status,
			'ar_status'     => 'publish',
			'en_title'      => $en[0],
			'en_summary'    => $en[1],
			'en_body'       => $en[2] ?? '',
			'ar_title'      => $ar[0],
			'ar_summary'    => $ar[1],
			'ar_body'       => $ar[2] ?? '',
			'icon'          => $icon,
			'quote_enabled' => $quote,
			'ad_spend_note' => $ad_spend ? 1 : 0,
			'created_at'    => db_now(),
			'updated_at'    => db_now(),
		)
	);
}

function vn_seed_projects(): void {
	$pos = 0;
	foreach ( vn_starter_projects() as $p ) {
		if ( db_val( 'SELECT id FROM vn_projects WHERE slug = ?', array( $p['slug'] ) ) ) {
			continue;
		}
		$id = db_insert(
			'vn_projects',
			array(
				'slug'        => $p['slug'],
				'position'    => $pos += 10,
				'status'      => 'draft',
				'ar_status'   => 'draft',
				'en_title'    => $p['en'][0],
				'en_summary'  => $p['en'][1],
				'en_body'     => vn_rich( $p['en'][2] ),
				'ar_title'    => $p['ar'][0],
				'ar_summary'  => $p['ar'][1],
				'ar_body'     => vn_rich( $p['ar'][2] ),
				'client_name' => $p['client'],
				'en_industry' => $p['industry'][0],
				'ar_industry' => $p['industry'][1],
				'en_scope'    => $p['scope'][0],
				'ar_scope'    => $p['scope'][1],
				'links'       => $p['links'] ?? '',
				'created_at'  => db_now(),
				'updated_at'  => db_now(),
			)
		);
		foreach ( $p['services'] as $slug ) {
			$sid = (int) db_val( 'SELECT id FROM vn_services WHERE slug = ?', array( $slug ) );
			if ( $sid ) {
				db_q( 'INSERT IGNORE INTO vn_project_services (project_id, service_id) VALUES (?, ?)', array( $id, $sid ) );
			}
		}
	}
}
