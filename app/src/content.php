<?php
/**
 * Website Content store: page copy, homepage sections, navigation and per-page SEO,
 * editable separately in English and Arabic from ViralNest > Website Content.
 *
 * Two records are kept in vn_kv: "content" (live) and "content_draft" (working copy).
 * Editors save drafts and preview them; administrators publish.
 * Starter copy below is written from the ViralNest Master Brief v2.0 (public-facing parts only;
 * internal parts of the brief are not used). [REVIEW] marks legal text.
 */

declare(strict_types=1);

/** Pages that have their own Arabic readiness switch. */
function vn_content_pages() {
	return array(
		'home'      => 'Home',
		'about'     => 'About',
		'services'  => 'Services overview',
		'portfolio' => 'Portfolio',
		'contact'   => 'Contact',
		'careers'   => 'Work with us',
		'quote'     => 'Request a Quote',
		'privacy'   => 'Privacy',
	);
}

/**
 * Field schema. Each field: [type, label, en default, ar default, review flag].
 * Types: text, textarea, lines (one item per line), rich (HTML).
 */
function vn_content_schema() {
	return array(
		'global'    => array(
			'label'  => 'Global (header, navigation, footer)',
			'fields' => array(
				'nav_services'  => array( 'text', 'Navigation: Services', 'Services', 'الخدمات' ),
				'nav_portfolio' => array( 'text', 'Navigation: Work', 'Work', 'شغلنا' ),
				'nav_about'     => array( 'text', 'Navigation: About', 'About', 'عننا' ),
				'nav_contact'   => array( 'text', 'Navigation: Contact', 'Contact', 'تواصل معانا' ),
				'nav_careers'   => array( 'text', 'Navigation: Work with us', 'Work with us', 'اشتغل معانا' ),
				'header_cta'    => array( 'text', 'Header button', 'Request a Quote', 'اطلب عرض سعر' ),
				'footer_text'   => array( 'textarea', 'Footer description', 'ViralNest is a hybrid digital marketing agency from Egypt. Agency-level professionalism, freelancer-like affordability, one integrated team.', 'ViralNest وكالة تسويق رقمي هجينة من مصر. احترافية الوكالات، بأسعار قريبة من الفريلانسرز، وفريق واحد متكامل.' ),
			),
		),
		'home'      => array(
			'label'  => 'Home',
			'fields' => array(
				'hero_eyebrow'    => array( 'text', 'Hero: small label', 'Hybrid digital marketing agency', 'وكالة تسويق رقمي هجينة' ),
				'hero_title'      => array( 'text', 'Hero: headline (tagline)', 'We make brands spread.', 'بنخلّي البراندات تنتشر.' ),
				'hero_text'       => array( 'textarea', 'Hero: positioning statement', 'Agency-level marketing at freelancer-like pricing. One organized team plans, creates, runs and measures your marketing, so you can get back to running your business.', 'تسويق بمستوى الوكالات وبأسعار قريبة من الفريلانسرز. فريق واحد منظّم بيخطط وينفّذ ويدير ويتابع التسويق بتاعك، وإنت تركّز في البيزنس بتاعك.' ),
				'hero_cta'        => array( 'text', 'Hero: primary button', 'Request a Quote', 'اطلب عرض سعر' ),
				'hero_cta2'       => array( 'text', 'Hero: secondary button', 'Explore services', 'اكتشف خدماتنا' ),

				'problem_title'   => array( 'text', 'Problem: heading', 'Freelancers or an agency? You shouldn’t have to choose.', 'فريلانسرز ولا وكالة؟ مش لازم تختار.' ),
				'problem_text'    => array( 'textarea', 'Problem: intro', 'Most growing businesses get stuck between two options, and both cost them something.', 'معظم البيزنسات اللي بتكبر بتلاقي نفسها بين اختيارين، والاتنين ليهم تمن.' ),
				'freelance_title' => array( 'text', 'Option 1: title', 'Hiring freelancers', 'إنك تجيب فريلانسرز' ),
				'freelance_list'  => array( 'lines', 'Option 1: points, one per line', "You manage a designer, a copywriter, a media buyer and more, one by one\nDeadlines, handoffs and follow-ups land on your desk\nKeeping everything consistent is your job\nNo one owns the overall marketing outcome", "بتدير ديزاينر وكوبي رايتر وميديا باير وغيرهم، كل واحد لوحده\nالمواعيد والتسليمات والمتابعة كلها عليك\nإنك تخلّي كل حاجة متناسقة بقت مسؤوليتك\nمحدش مسؤول عن نتيجة التسويق ككل" ),
				'agency_title'    => array( 'text', 'Option 2: title', 'A traditional agency', 'وكالة تقليدية' ),
				'agency_list'     => array( 'lines', 'Option 2: points, one per line', "An integrated team and organized management\nPricing many startups and small businesses can’t justify\nPackages that don’t always match what you need", "فريق متكامل وإدارة منظّمة\nبس بأسعار كتير من الشركات الناشئة والصغيرة مش قادرة عليها\nوباقات مش دايمًا على مقاس اللي محتاجه" ),
				'solution_title'  => array( 'text', 'ViralNest: title', 'ViralNest', 'ViralNest' ),
				'solution_list'   => array( 'lines', 'ViralNest: points, one per line', "One organized team and one point of contact\nAgency-level planning, execution and reporting\nFlexible pricing shaped around what you need\nWe coordinate the specialists. You run your business.", "فريق واحد منظّم ومسؤول تواصل واحد\nتخطيط وتنفيذ وتقارير بمستوى الوكالات\nأسعار مرنة على حسب اللي محتاجه\nإحنا بننسّق المتخصصين، وإنت بتدير البيزنس بتاعك" ),

				'value_title'     => array( 'text', 'Value: heading (positioning)', 'Agency-level professionalism. Freelancer-like affordability. One integrated team.', 'احترافية الوكالات. أسعار قريبة من الفريلانسرز. فريق واحد متكامل.' ),
				'value_text'      => array( 'textarea', 'Value: intro', 'We’re not trying to be the cheapest option. We make professional, integrated marketing accessible to businesses that can’t justify the traditional agency model.', 'إحنا مش بنحاول نكون الأرخص. إحنا بنخلّي التسويق الاحترافي المتكامل في متناول البيزنسات اللي مش مناسب لها نموذج الوكالات التقليدي.' ),
				'value1_title'    => array( 'text', 'Value 1: title', 'Agency-level professionalism', 'احترافية الوكالات' ),
				'value1_text'     => array( 'textarea', 'Value 1: text', 'Organized execution, professional communication, strategic thinking and coordinated specialists.', 'تنفيذ منظّم، وتواصل احترافي، وتفكير استراتيجي، ومتخصصين شغالين بتنسيق.' ),
				'value2_title'    => array( 'text', 'Value 2: title', 'Freelancer-like affordability', 'أسعار قريبة من الفريلانسرز' ),
				'value2_text'     => array( 'textarea', 'Value 2: text', 'Flexible pricing built around your needs, not expensive fixed packages.', 'أسعار مرنة مبنية على احتياجك، مش باقات ثابتة غالية.' ),
				'value3_title'    => array( 'text', 'Value 3: title', 'One point of contact', 'مسؤول تواصل واحد' ),
				'value3_text'     => array( 'textarea', 'Value 3: text', 'You talk to one organized team instead of managing a crowd of freelancers.', 'بتتعامل مع فريق واحد منظّم بدل ما تدير كذا فريلانسر.' ),
				'value4_title'    => array( 'text', 'Value 4: title', 'Integrated marketing', 'تسويق متكامل' ),
				'value4_text'     => array( 'textarea', 'Value 4: text', 'Strategy, content, design, ads and SEO work under one coordinated plan.', 'الاستراتيجية والمحتوى والتصميم والإعلانات والـSEO شغالين تحت خطة واحدة متنسّقة.' ),
				'value5_title'    => array( 'text', 'Value 5: title', 'Custom solutions', 'حلول على مقاسك' ),
				'value5_text'     => array( 'textarea', 'Value 5: text', 'Buy one service or hand us your complete marketing. You decide the scope.', 'اشتري خدمة واحدة أو سيب لنا التسويق كله. إنت اللي بتحدد النطاق.' ),

				'services_title'  => array( 'text', 'Services: heading', 'Services across the digital marketing ecosystem', 'خدمات بتغطي التسويق الرقمي كله' ),
				'services_text'   => array( 'textarea', 'Services: intro', 'More than posting and ads. From research and strategy to content, design, media buying, branding, SEO and campaigns, delivered by one coordinated team.', 'أكتر من بوستات وإعلانات. من البحث والاستراتيجية للمحتوى والتصميم والإعلانات والبراندينج والـSEO والحملات، وكله بينفّذه فريق واحد متنسّق.' ),

				'models_title'    => array( 'text', 'Ways to work: heading', 'Three ways to work with us', 'تلات طرق تشتغل بيها معانا' ),
				'model1_title'    => array( 'text', 'Way 1: title', 'Full marketing management', 'إدارة التسويق بالكامل' ),
				'model1_text'     => array( 'textarea', 'Way 1: text', 'Outsource your complete digital marketing to one team. Depending on scope: strategy, content, design, social media, advertising, SEO and performance reporting.', 'سيب التسويق الرقمي كله لفريق واحد. وعلى حسب النطاق: استراتيجية، ومحتوى، وتصميم، وسوشيال ميديا، وإعلانات، وSEO، وتقارير أداء.' ),
				'model2_title'    => array( 'text', 'Way 2: title', 'Individual services', 'خدمات منفصلة' ),
				'model2_text'     => array( 'textarea', 'Way 2: text', 'Only need media buying, design, content, social media, SEO or branding? Buy the one service you need.', 'محتاج ميديا باينج بس، أو تصميم، أو محتوى، أو سوشيال ميديا، أو SEO، أو براندينج؟ خد الخدمة اللي محتاجها بس.' ),
				'model3_title'    => array( 'text', 'Way 3: title', 'Custom marketing solutions', 'حلول تسويق مخصّصة' ),
				'model3_text'     => array( 'textarea', 'Way 3: text', 'We combine the services you need, or fill the gaps in your in-house team with missing specializations and extra hands.', 'بنجمع الخدمات اللي محتاجها، أو بنكمّل فريقك الداخلي بالتخصصات الناقصة أو بإيد زيادة وقت الضغط.' ),
				'models_note'     => array( 'text', 'Ways to work: note', 'You’re never required to buy a full-service package to work with us.', 'عمرك ما هتكون مضطر تشتري باقة كاملة عشان تشتغل معانا.' ),

				'audience_title'  => array( 'text', 'Who we work with: heading', 'Built for startups. Ready for growing businesses.', 'معمولين للشركات الناشئة. وجاهزين للبيزنسات اللي بتكبر.' ),
				'audience_text'   => array( 'textarea', 'Who we work with: text', 'Many startups have no marketing department yet and need to build a brand from zero: research, positioning and an organized digital presence. That’s where we do our best work. We also work with small and medium-sized businesses, growing and established companies, and teams that already have in-house marketers.', 'شركات ناشئة كتير لسه معندهاش قسم تسويق ومحتاجة تبني البراند من الصفر: بحث، وتموضع، وحضور ديجيتال منظّم. وده المكان اللي بنقدّم فيه أحسن شغلنا. وكمان بنشتغل مع الشركات الصغيرة والمتوسطة، والشركات اللي بتكبر والشركات الكبيرة، والفرق اللي عندها تسويق داخلي.' ),
				'markets_text'    => array( 'textarea', 'Markets: text', 'Based in Egypt and working remotely with businesses in Egypt, the Gulf, the United States and other markets.', 'مقرنا مصر، وبنشتغل أونلاين مع بيزنسات في مصر والخليج وأمريكا وأسواق تانية.' ),
				'industries_title'=> array( 'text', 'Industries: heading', 'Industries we work with', 'مجالات بنشتغل فيها' ),
				'industries_list' => array( 'lines', 'Industries: one per line', "E-commerce\nRestaurants and food\nBeauty and skincare\nAesthetic and laser clinics\nHealthcare, within marketing and regulatory rules\nReal estate\nHospitality\nShort-term rentals\nRetail\nEducation\nTech startups\nProfessional services\nLocal businesses", "التجارة الإلكترونية\nالمطاعم والأكل\nالبيوتي والعناية بالبشرة\nعيادات التجميل والليزر\nالرعاية الصحية، في حدود قواعد التسويق واللوائح\nالعقارات\nالضيافة\nالإيجارات قصيرة المدة\nالتجزئة\nالتعليم\nالشركات الناشئة التكنولوجية\nالخدمات المهنية\nالبيزنسات المحلية" ),
				'industries_note' => array( 'text', 'Industries: note', 'Products or services, local or international: we adapt the plan to your market.', 'منتجات أو خدمات، محلي أو دولي: بنظبط الخطة على السوق بتاعك.' ),

				'work_title'      => array( 'text', 'Featured work: heading', 'Selected work', 'من شغلنا' ),
				'work_text'       => array( 'textarea', 'Featured work: intro', 'A look at work we’ve done for clients who agreed to be featured.', 'نظرة على شغل عملناه لعملاء وافقوا إننا نعرضه.' ),

				'process_title'   => array( 'text', 'Process: heading', 'How we work with you', 'إزاي بنشتغل معاك' ),
				'process_text'    => array( 'textarea', 'Process: intro', 'Research before strategy, strategy before execution, and clear reporting the whole way.', 'البحث قبل الاستراتيجية، والاستراتيجية قبل التنفيذ، وتقارير واضحة طول الطريق.' ),
				'step1_title'     => array( 'text', 'Step 1: title', 'Discovery', 'نفهمك' ),
				'step1_text'      => array( 'textarea', 'Step 1: text', 'We learn your business model, offer, audience, current marketing, goals, resources and budget.', 'بنفهم نموذج البيزنس، واللي بتقدّمه، وجمهورك، والتسويق الحالي، وأهدافك، ومواردك، والميزانية.' ),
				'step2_title'     => array( 'text', 'Step 2: title', 'Research', 'نبحث' ),
				'step2_text'      => array( 'textarea', 'Step 2: text', 'Market, competitor and audience research, plus a review of your brand and digital presence.', 'بحث للسوق والمنافسين والجمهور، ومراجعة للبراند والحضور الديجيتال بتاعك.' ),
				'step3_title'     => array( 'text', 'Step 3: title', 'Strategy', 'نخطط' ),
				'step3_text'      => array( 'textarea', 'Step 3: text', 'A marketing strategy built on that research and your objectives.', 'استراتيجية تسويق مبنية على البحث ده وعلى أهدافك.' ),
				'step4_title'     => array( 'text', 'Step 4: title', 'Proposal', 'نقدّم العرض' ),
				'step4_text'      => array( 'textarea', 'Step 4: text', 'Recommended services, scope, deliverables, timeline, pricing and reporting, in writing.', 'الخدمات المقترحة، ونطاق الشغل، والتسليمات، والمواعيد، والسعر، والتقارير، وكله مكتوب.' ),
				'step5_title'     => array( 'text', 'Step 5: title', 'Onboarding', 'نبدأ' ),
				'step5_text'      => array( 'textarea', 'Step 5: text', 'Agreement, access and brand assets, then communication and project setup.', 'الاتفاق، والصلاحيات، وملفات البراند، وبعدها نجهّز التواصل والمشروع.' ),
				'step6_title'     => array( 'text', 'Step 6: title', 'Execution', 'ننفّذ' ),
				'step6_text'      => array( 'textarea', 'Step 6: text', 'Our team and the specialists assigned to your account deliver the agreed work.', 'فريقنا والمتخصصين اللي على حسابك بينفّذوا الشغل المتفق عليه.' ),
				'step7_title'     => array( 'text', 'Step 7: title', 'Monitor and optimize', 'نتابع ونحسّن' ),
				'step7_text'      => array( 'textarea', 'Step 7: text', 'We watch performance, spot issues early and keep improving.', 'بنتابع الأداء، ونلحق المشاكل بدري، ونفضل نحسّن.' ),
				'step8_title'     => array( 'text', 'Step 8: title', 'Report and grow', 'نقيس ونكبر' ),
				'step8_text'      => array( 'textarea', 'Step 8: text', 'Regular reports, progress reviews and the next opportunities to grow.', 'تقارير منتظمة، ومراجعة للتقدم، والفرص الجاية للنمو.' ),

				'cta_title'       => array( 'text', 'Closing CTA: heading', 'Ready to make your brand spread?', 'جاهز تخلّي البراند بتاعك ينتشر؟' ),
				'cta_text'        => array( 'textarea', 'Closing CTA: text', 'Tell us what you need. We’ll start with your goals and come back with a proposal built around your scope and your market.', 'قولنا محتاج إيه. هنبدأ من أهدافك ونرجعلك بعرض مبني على نطاق شغلك والسوق بتاعك.' ),
				'cta_button'      => array( 'text', 'Closing CTA: button', 'Request a Quote', 'اطلب عرض سعر' ),
			),
		),
		'about'     => array(
			'label'  => 'About',
			'fields' => array(
				'title'          => array( 'text', 'Headline', 'Professional marketing, made accessible.', 'تسويق احترافي، في متناولك.' ),
				'intro'          => array( 'textarea', 'Intro', 'ViralNest is a hybrid digital marketing agency from Egypt. We combine the structure and quality of a traditional agency with the flexibility and affordability of working with freelancers.', 'ViralNest وكالة تسويق رقمي هجينة من مصر. بنجمع بين تنظيم وجودة الوكالات التقليدية، ومرونة وأسعار الشغل مع الفريلانسرز.' ),
				'story_title'    => array( 'text', 'Story: heading', 'Why ViralNest exists', 'ليه عملنا ViralNest' ),
				'story_text'     => array( 'lines', 'Story: paragraphs, one per line', "Growing businesses usually face two options. Hire several freelancers and spend your time coordinating them, chasing deadlines and stitching their work together. Or hire a traditional agency with an integrated team, at a price many startups and small businesses can’t afford.\nViralNest combines the advantages of both. You work with one organized team instead of managing independent specialists, and we take responsibility for coordination, planning, execution and performance monitoring within the agreed scope.", "البيزنسات اللي بتكبر عادةً قدامها اختيارين. يا تجيب كذا فريلانسر وتقضي وقتك تنسّق بينهم، وتجري ورا المواعيد، وتلم شغلهم على بعض. يا تتعامل مع وكالة تقليدية عندها فريق متكامل، بس بأسعار شركات ناشئة وصغيرة كتير مش قادرة عليها.\nViralNest بتجمع مميزات الاتنين. بتتعامل مع فريق واحد منظّم بدل ما تدير متخصصين كل واحد لوحده، وإحنا بنشيل مسؤولية التنسيق والتخطيط والتنفيذ ومتابعة الأداء في حدود النطاق المتفق عليه." ),
				'model_title'    => array( 'text', 'Agency model: heading', 'A hybrid agency model', 'نموذج وكالة هجين' ),
				'model_text'     => array( 'textarea', 'Agency model: text', 'A core team leads every account and brings in specialists according to each project’s needs. You get access to many specializations, flexible capacity and one place where everything is managed.', 'فريق أساسي بيقود كل حساب، وبيستعين بمتخصصين على حسب احتياج كل مشروع. فبتوصل لتخصصات كتير، وقدرة مرنة، ومكان واحد بيتدار منه كل حاجة.' ),
				'approach_title' => array( 'text', 'Approach: heading', 'Solutions, not post counts', 'حلول، مش عدد بوستات' ),
				'approach_text'  => array( 'textarea', 'Approach: text', 'Every plan starts from your business, your market and your audience. We explain how the work connects to your marketing and business goals, and we keep it honest: no unrealistic growth promises.', 'كل خطة بتبدأ من البيزنس بتاعك، والسوق، والجمهور. وبنوضّح إزاي الشغل بيخدم أهدافك التسويقية وأهداف البيزنس، وبنفضل صرحاء: مفيش وعود نمو مش واقعية.' ),
				'mission_title'  => array( 'text', 'Mission: heading', 'Our mission', 'مهمتنا' ),
				'mission_text'   => array( 'textarea', 'Mission: text', 'To make professional digital marketing accessible, organized and effective for businesses of different sizes and stages of growth.', 'إننا نخلّي التسويق الرقمي الاحترافي متاح ومنظّم وفعّال للبيزنسات بكل أحجامها ومراحل نموها.' ),
				'voice_title'    => array( 'text', 'How we show up: heading', 'How we show up', 'إحنا مين في الشغل' ),
				'voice_list'     => array( 'lines', 'How we show up: one per line', "Young, but professional\nCreative, but strategic\nFriendly, but confident\nModern, but trustworthy", "شباب، بس محترفين\nمبدعين، بس استراتيجيين\nقريبين منك، بس واثقين\nمودرن، بس تقدر تثق فينا" ),
				'expect_title'   => array( 'text', 'Expectations: heading', 'What you can expect', 'تقدر تتوقع مننا إيه' ),
				'expect_list'    => array( 'lines', 'Expectations: one per line', "One point of contact and one coordinated team\nResearch and strategy before execution\nA written proposal with scope, deliverables, timeline and pricing\nPricing shaped around your needs and your market\nRegular performance reporting\nHonest expectations, with no unrealistic growth promises", "مسؤول تواصل واحد وفريق واحد متنسّق\nبحث واستراتيجية قبل التنفيذ\nعرض مكتوب فيه النطاق والتسليمات والمواعيد والسعر\nأسعار على حسب احتياجك والسوق بتاعك\nتقارير أداء منتظمة\nتوقعات واقعية، من غير وعود نمو مبالغ فيها" ),
			),
		),
		'services'  => array(
			'label'  => 'Services overview',
			'fields' => array(
				'title'         => array( 'text', 'Headline', 'Services across the digital marketing ecosystem', 'خدمات بتغطي التسويق الرقمي كله' ),
				'intro'         => array( 'textarea', 'Intro', 'Buy one service, combine the ones you need, or hand us your complete marketing. Every option is delivered by one coordinated team.', 'خد خدمة واحدة، أو اجمع الخدمات اللي محتاجها، أو سيب لنا التسويق كله. وكل الاختيارات بينفّذها فريق واحد متنسّق.' ),
				'pricing_title' => array( 'text', 'Pricing: heading', 'How pricing works', 'الأسعار بتتحسب إزاي' ),
				'pricing_text'  => array( 'textarea', 'Pricing: text', 'There are no fixed packages. Every proposal is priced around what you actually need: your goals, services, platforms, content volume, production and timeline. Pricing is set for your market too, in USD for the United States, EGP for Egypt and local pricing for the Gulf and other markets. Advertising budgets are paid separately from our management fees.', 'مفيش باقات ثابتة. كل عرض بيتسعّر على حسب اللي إنت محتاجه فعلًا: أهدافك، والخدمات، والمنصات، وكمية المحتوى، والإنتاج، والمدة. والسعر كمان بيتحدد على حسب السوق: بالدولار لأمريكا، وبالجنيه لمصر، وأسعار محلية للخليج والأسواق التانية. وميزانية الإعلانات بتتدفع منفصلة عن أتعاب الإدارة.' ),
				'other_title'   => array( 'text', 'Other services: heading', 'Need something else?', 'محتاج حاجة تانية؟' ),
				'other_text'    => array( 'textarea', 'Other services: text', 'We may take on other digital marketing work depending on your scope. We’ll only confirm it once we’re sure we can deliver it professionally.', 'ممكن نشتغل على خدمات تسويق رقمي تانية على حسب نطاق شغلك. وبنأكدها بس لما نكون متأكدين إننا هننفّذها باحترافية.' ),
			),
		),
		'portfolio' => array(
			'label'  => 'Portfolio',
			'fields' => array(
				'title'       => array( 'text', 'Headline', 'Work that spreads', 'شغل بينتشر' ),
				'intro'       => array( 'textarea', 'Intro', 'Selected brands and projects across strategy, content, design and campaigns, shown with our clients’ permission. Each case study explains the challenge, what we did and what we delivered.', 'براندات ومشاريع مختارة في الاستراتيجية والمحتوى والتصميم والحملات، معروضة بإذن عملائنا. كل مشروع بيوضّح التحدي، وعملنا إيه، وسلّمنا إيه.' ),
				'filter_all'  => array( 'text', 'Filter: all', 'All work', 'كل الشغل' ),
				'empty_title' => array( 'text', 'Empty state: heading', 'Case studies are on the way', 'دراسات الحالة جاية قريب' ),
				'empty_text'  => array( 'textarea', 'Empty state: text', 'We’re preparing case studies with our clients’ approval. Tell us about your project and we’ll share relevant examples on a call.', 'بنجهّز دراسات الحالة بموافقة عملائنا. احكيلنا عن مشروعك وهنوريك أمثلة مناسبة في مكالمة.' ),
				'cta_title'   => array( 'text', 'Closing CTA: heading', 'Have a project in mind?', 'عندك مشروع في دماغك؟' ),
				'cta_text'    => array( 'textarea', 'Closing CTA: text', 'Tell us where your brand is today and where you want it to go. We’ll come back with a plan and a written proposal.', 'قولنا البراند بتاعك فين النهارده وعايز توصل لفين، وهنرجعلك بخطة وعرض مكتوب.' ),
			),
		),
		'careers'   => array(
			'label'  => 'Work with us (freelancers)',
			'fields' => array(
				'eyebrow'      => array( 'text', 'Small label', 'Freelancers and specialists', 'للفريلانسرز والمتخصصين' ),
				'title'        => array( 'text', 'Headline', 'Work with ViralNest', 'اشتغل مع ViralNest' ),
				'intro'        => array( 'textarea', 'Intro', 'We’re a hybrid agency: a core team that leads every account, and trusted specialists who join the projects that need their skills. If you’re great at what you do, we’d like to hear from you.', 'إحنا وكالة هجينة: فريق أساسي بيقود كل حساب، ومتخصصين بنثق فيهم بينضموا للمشاريع اللي محتاجة مهاراتهم. لو إنت شاطر في اللي بتعمله، يهمنا نسمع منك.' ),
				'why_title'    => array( 'text', 'Why join: heading', 'Why freelancers work with us', 'ليه الفريلانسرز بيشتغلوا معانا' ),
				'why_list'     => array( 'lines', 'Why join: one per line (title: text)', "Clear briefs: Every task comes with the goal, the audience and the brand guidelines.
One point of contact: You work with our account lead, not a chain of messages.
Organized workflow: Agreed scope, deadlines and feedback rounds before work starts.
Fair, on-time payment: Rates and payment terms are agreed in writing for each project.
Varied brands: Work across e-commerce, food, beauty, real estate, startups and more.
Grow with the team: Specialists who deliver well get more projects with us.", "بريف واضح: كل تاسك بييجي معاه الهدف والجمهور وهوية البراند.
مسؤول تواصل واحد: بتشتغل مع الأكاونت ليد بتاعنا، مش سلسلة رسايل.
شغل منظّم: النطاق والمواعيد وجولات التعديل متفق عليها قبل ما نبدأ.
دفع عادل وفي معاده: السعر وطريقة الدفع بيتكتبوا لكل مشروع.
براندات متنوعة: تجارة إلكترونية، أكل، بيوتي، عقارات، شركات ناشئة وغيرهم.
كبّر معانا: المتخصص اللي بيسلّم كويس بياخد مشاريع أكتر معانا." ),
				'roles_title'  => array( 'text', 'Roles: heading', 'Specialists we work with', 'التخصصات اللي بنشتغل معاها' ),
				'roles_text'   => array( 'textarea', 'Roles: intro', 'We bring in specialists project by project. Apply once and we’ll reach out when a project matches your skills.', 'بنستعين بالمتخصصين على حسب كل مشروع. قدّم مرة واحدة وهنكلّمك لما ييجي مشروع مناسب لمهاراتك.' ),
				'steps_title'  => array( 'text', 'Process: heading', 'How it works', 'الخطوات' ),
				'steps_list'   => array( 'lines', 'Process: one per line', "Apply with your work samples
We review your portfolio
A short call to get to know you
A paid trial task when a project fits
You join our specialist network", "قدّم وابعت نماذج من شغلك
بنراجع البورتفوليو بتاعك
مكالمة قصيرة نتعرف فيها عليك
تاسك تجريبي مدفوع لما ييجي مشروع مناسب
تنضم لشبكة المتخصصين بتاعتنا" ),
				'form_title'   => array( 'text', 'Form: heading', 'Apply to join our specialist network', 'قدّم وانضم لشبكة المتخصصين' ),
				'form_text'    => array( 'textarea', 'Form: intro', 'It takes about three minutes. Links to your best work matter more than a CV.', 'هتاخد حوالي ٣ دقايق. لينكات أحسن شغلك أهم من السي ڤي.' ),
				'success_title'=> array( 'text', 'Success: heading', 'Application received', 'وصلنا طلبك' ),
				'success_text' => array( 'textarea', 'Success: text', 'Thanks for applying. We review every application and will contact you when a project matches your skills.', 'شكرًا إنك قدّمت. بنراجع كل الطلبات وهنتواصل معاك لما ييجي مشروع مناسب لمهاراتك.' ),
			),
		),
		'contact'   => array(
			'label'  => 'Contact',
			'fields' => array(
				'title' => array( 'text', 'Headline', 'Let’s talk', 'يلا نتكلم' ),
				'intro' => array( 'textarea', 'Intro', 'Reach us on the channel that suits you, or send a quote request and we’ll come back to you.', 'كلّمنا على القناة اللي تريّحك، أو ابعت طلب عرض سعر وهنرجعلك.' ),
			),
		),
		'quote'     => array(
			'label'  => 'Request a Quote',
			'fields' => array(
				'title'       => array( 'text', 'Headline', 'Request a Quote', 'اطلب عرض سعر' ),
				'intro'       => array( 'textarea', 'Intro', 'Pick one service or several. You never have to buy a full package. Share a few details and we’ll take it from there.', 'اختار خدمة واحدة أو أكتر، ومش لازم تاخد باقة كاملة. قولنا شوية تفاصيل وإحنا هنكمّل معاك.' ),
				'aside_title' => array( 'text', 'Side panel: heading', 'What happens next', 'إيه اللي هيحصل بعد كده' ),
				'aside_list'  => array( 'lines', 'Side panel: steps, one per line', "We review your request\nWe set up a discovery call to understand your business and goals\nWe research and shape the right mix of services\nYou receive a written proposal with scope, deliverables, timeline and pricing", "بنراجع طلبك\nبنحدد معاك مكالمة نفهم فيها البيزنس وأهدافك\nبنعمل البحث ونختار الخدمات المناسبة\nبتستلم عرض مكتوب فيه النطاق والتسليمات والمواعيد والسعر" ),
			),
		),
		'privacy'   => array(
			'label'  => 'Privacy',
			'fields' => array(
				'title' => array( 'text', 'Headline', 'Privacy policy', 'سياسة الخصوصية' ),
				'body'  => array( 'rich', 'Policy text [REVIEW with legal counsel]', vn_default_privacy_en(), vn_default_privacy_ar(), true ),
			),
		),
		'e404'      => array(
			'label'  => '404 page',
			'fields' => array(
				'text' => array( 'textarea', 'Message', 'We couldn’t find that page. Try one of these links instead.', 'مش لاقيين الصفحة دي. جرّب واحد من اللينكات دي.' ),
			),
		),
		'seo'       => array(
			'label'  => 'SEO titles and descriptions',
			'fields' => array(
				'home_title'       => array( 'text', 'Home: title', 'ViralNest | We make brands spread', 'ViralNest | بنخلّي البراندات تنتشر' ),
				'home_desc'        => array( 'textarea', 'Home: description', 'Hybrid digital marketing agency from Egypt: agency-level professionalism, freelancer-like affordability and one integrated team for strategy, content, design, ads, branding and SEO.', 'وكالة تسويق رقمي هجينة من مصر: احترافية الوكالات وأسعار قريبة من الفريلانسرز، وفريق واحد للاستراتيجية والمحتوى والتصميم والإعلانات والبراندينج والـSEO.' ),
				'about_title'      => array( 'text', 'About: title', 'About ViralNest | Hybrid digital marketing agency', 'عن ViralNest | وكالة تسويق رقمي هجينة' ),
				'about_desc'       => array( 'textarea', 'About: description', 'Why ViralNest exists, how our hybrid agency model works, and what startups and growing businesses can expect from one coordinated team.', 'ليه عملنا ViralNest، وإزاي نموذج الوكالة الهجين بيشتغل، وتقدر تتوقع إيه من فريق واحد متنسّق.' ),
				'services_title'   => array( 'text', 'Services: title', 'Digital marketing services | ViralNest', 'خدمات التسويق الرقمي | ViralNest' ),
				'services_desc'    => array( 'textarea', 'Services: description', 'Marketing strategy and research, social media management, content, graphic design, Meta Ads, branding, SEO, creative campaigns, and PR and influencer marketing with UGC creators and bloggers. Buy one service or all of them.', 'استراتيجية وأبحاث تسويق، إدارة سوشيال ميديا، محتوى، تصميم، إعلانات ميتا، براندينج، SEO، حملات إبداعية، وعلاقات عامة وتسويق بالمؤثرين مع صنّاع محتوى UGC وبلوجرز. خد خدمة واحدة أو كلهم.' ),
				'portfolio_title'  => array( 'text', 'Portfolio: title', 'Our work | ViralNest', 'شغلنا | ViralNest' ),
				'portfolio_desc'   => array( 'textarea', 'Portfolio: description', 'Selected ViralNest projects across strategy, content, design and campaigns.', 'مشاريع مختارة من شغل ViralNest في الاستراتيجية والمحتوى والتصميم والحملات.' ),
				'contact_title'    => array( 'text', 'Contact: title', 'Contact ViralNest', 'تواصل مع ViralNest' ),
				'contact_desc'     => array( 'textarea', 'Contact: description', 'Get in touch with ViralNest or request a quote for your marketing.', 'تواصل مع ViralNest أو اطلب عرض سعر للتسويق بتاعك.' ),
				'quote_title'      => array( 'text', 'Quote: title', 'Request a Quote | ViralNest', 'اطلب عرض سعر | ViralNest' ),
				'quote_desc'       => array( 'textarea', 'Quote: description', 'Tell us which services you need and get a written proposal based on your scope and market.', 'قولنا محتاج أنهي خدمات وخد عرض مكتوب مبني على نطاق شغلك والسوق بتاعك.' ),
				'careers_title'    => array( 'text', 'Work with us: title', 'Work with us | Freelancers | ViralNest', 'اشتغل معانا | فريلانسرز | ViralNest' ),
				'careers_desc'     => array( 'textarea', 'Work with us: description', 'Designers, video editors, content creators, copywriters, SEO specialists, developers and media buyers: apply to join the ViralNest specialist network.', 'ديزاينرز، مونتيرين، صناع محتوى، كوبي رايترز، متخصصين SEO، مطورين وميديا بايرز: قدّم وانضم لشبكة متخصصين ViralNest.' ),
				'privacy_title'    => array( 'text', 'Privacy: title', 'Privacy policy | ViralNest', 'سياسة الخصوصية | ViralNest' ),
				'privacy_desc'     => array( 'textarea', 'Privacy: description', 'How ViralNest collects, uses and protects information submitted through this website.', 'إزاي ViralNest بتجمع وبتستخدم وبتحمي البيانات اللي بتتبعت من خلال الموقع.' ),
			),
		),
	);
}

function vn_default_privacy_en() {
	return '<p><strong>Draft for legal review.</strong> Replace this text with the approved policy before launch.</p>
<h2>What we collect</h2>
<p>When you send a quote request we collect the services you selected, your email address and phone number, and any optional details you choose to give (name, business name, website and project description). We also record the language of the form, the page where you started your visit, the referring website, and campaign tags (UTM parameters) if the link you followed had them.</p>
<h2>How we use it</h2>
<p>We use your request only to respond to you, prepare a proposal and manage our relationship with you. We do not sell your information.</p>
<h2>Analytics</h2>
<p>With your permission, this website records anonymous usage events such as page views and button clicks to understand which pages help visitors. These events do not include your name, email, phone number or message. Optional third-party analytics, if enabled, run only after you allow analytics. You can change your choice at any time from "Privacy settings" in the footer.</p>
<h2>Retention</h2>
<p>Quote requests are kept for as long as needed to respond and manage the relationship, and analytics events are deleted automatically after a set period.</p>
<h2>Your choices</h2>
<p>You can ask us to access, correct or delete the information you sent through this website using the contact channels on our Contact page.</p>';
}

function vn_default_privacy_ar() {
	return '<p><strong>مسودة للمراجعة القانونية.</strong> استبدل النص ده بالسياسة المعتمدة قبل الإطلاق.</p>
<h2>بنجمع إيه</h2>
<p>لما تبعت طلب عرض سعر، بنجمع الخدمات اللي اخترتها، والإيميل ورقم التليفون، وأي تفاصيل اختيارية تحب تضيفها (الاسم، اسم البيزنس، الموقع، ووصف المشروع). وكمان بنسجّل لغة الفورم، والصفحة اللي بدأت منها زيارتك، والموقع اللي جيت منه، وأكواد الحملات (UTM) لو اللينك اللي دخلت منه كان فيه.</p>
<h2>بنستخدمها في إيه</h2>
<p>بنستخدم طلبك بس عشان نرد عليك، ونجهّز عرض، وندير تعاملنا معاك. ومش بنبيع بياناتك.</p>
<h2>التحليلات</h2>
<p>بموافقتك، الموقع بيسجّل أحداث استخدام مجهولة زي مشاهدة الصفحات والضغط على الزراير، عشان نفهم أنهي صفحات بتفيد الزوار. الأحداث دي مفيهاش اسمك ولا إيميلك ولا رقمك ولا رسالتك. والتحليلات الخارجية الاختيارية، لو اتفعّلت، بتشتغل بس بعد ما توافق. تقدر تغيّر اختيارك في أي وقت من "إعدادات الخصوصية" في آخر الصفحة.</p>
<h2>مدة الاحتفاظ</h2>
<p>طلبات عروض الأسعار بنحتفظ بيها طول ما هي لازمة للرد وإدارة التعامل، وأحداث التحليلات بتتمسح تلقائيًا بعد مدة محددة.</p>
<h2>اختياراتك</h2>
<p>تقدر تطلب مننا تشوف أو تصحّح أو تمسح البيانات اللي بعتها من خلال الموقع، عن طريق قنوات التواصل اللي في صفحة تواصل معانا.</p>';
}

/** Default homepage section order (the hero is always first). */
function vn_default_home_sections() {
	return array(
		array( 'id' => 'marquee', 'on' => 1 ),
		array( 'id' => 'problem', 'on' => 1 ),
		array( 'id' => 'value', 'on' => 1 ),
		array( 'id' => 'facts', 'on' => 1 ),
		array( 'id' => 'services', 'on' => 1 ),
		array( 'id' => 'models', 'on' => 1 ),
		array( 'id' => 'audience', 'on' => 1 ),
		array( 'id' => 'work', 'on' => 1 ),
		array( 'id' => 'process', 'on' => 1 ),
		array( 'id' => 'careers', 'on' => 1 ),
		array( 'id' => 'cta', 'on' => 1 ),
	);
}

function vn_default_nav() {
	return array(
		array( 'route' => 'services', 'on' => 1 ),
		array( 'route' => 'portfolio', 'on' => 1 ),
		array( 'route' => 'about', 'on' => 1 ),
		array( 'route' => 'careers', 'on' => 1 ),
		array( 'route' => 'contact', 'on' => 1 ),
	);
}

/** Build the default store from the schema. */
function vn_default_content() {
	$store = array(
		'fields'        => array( 'en' => array(), 'ar' => array() ),
		'ar_ready'      => array_fill_keys( array_keys( vn_content_pages() ), 1 ),
		'home_sections' => vn_default_home_sections(),
		'nav'           => vn_default_nav(),
	);
	foreach ( vn_content_schema() as $section => $def ) {
		foreach ( $def['fields'] as $key => $f ) {
			$store['fields']['en'][ "$section.$key" ] = $f[2];
			$store['fields']['ar'][ "$section.$key" ] = $f[3];
		}
	}
	return $store;
}

/** Whether the current request is a logged-in content preview (?vn_preview=<token>). */
function vn_is_content_preview(): bool {
	static $is = null;
	if ( null === $is ) {
		$token = isset( $_GET['vn_preview'] ) ? (string) $_GET['vn_preview'] : '';
		$is    = '' !== $token && vn_can( 'content.edit' ) && hash_equals( vn_preview_token(), $token );
	}
	return $is;
}

/**
 * Get the content store.
 *
 * @param string $which live|draft|auto (auto = draft during preview).
 */
function vn_content_store( $which = 'auto' ) {
	if ( 'auto' === $which ) {
		$which = vn_is_content_preview() ? 'draft' : 'live';
	}
	$saved = kv_get( 'draft' === $which ? 'content_draft' : 'content' );
	if ( ! $saved && 'draft' === $which ) {
		$saved = kv_get( 'content' );
	}
	$defaults = vn_default_content();
	if ( ! is_array( $saved ) ) {
		return $defaults;
	}
	foreach ( array( 'en', 'ar' ) as $l ) {
		$saved['fields'][ $l ] = array_merge( $defaults['fields'][ $l ], $saved['fields'][ $l ] ?? array() );
	}
	$saved['ar_ready']      = array_merge( $defaults['ar_ready'], $saved['ar_ready'] ?? array() );
	$saved['home_sections'] = $saved['home_sections'] ?? $defaults['home_sections'];
	// Sections added in later versions appear (switched on) in their default position.
	$have = array_column( $saved['home_sections'], 'id' );
	foreach ( $defaults['home_sections'] as $pos => $sec ) {
		if ( ! in_array( $sec['id'], $have, true ) ) {
			array_splice( $saved['home_sections'], min( $pos, count( $saved['home_sections'] ) ), 0, array( $sec ) );
			$have[] = $sec['id'];
		}
	}
	$saved['nav'] = $saved['nav'] ?? $defaults['nav'];
	$have_nav     = array_column( $saved['nav'], 'route' );
	foreach ( $defaults['nav'] as $pos => $item ) {
		if ( ! in_array( $item['route'], $have_nav, true ) ) {
			array_splice( $saved['nav'], min( $pos, count( $saved['nav'] ) ), 0, array( $item ) );
		}
	}
	return $saved;
}

/**
 * Read a content field.
 *
 * @param string      $key  "section.field".
 * @param string|null $lang Language.
 */
function vn_c( $key, $lang = null ) {
	static $cache = array();
	if ( ! empty( $GLOBALS['vn_content_dirty'] ) ) {
		$cache = array();
		unset( $GLOBALS['vn_content_dirty'] );
	}
	$lang = $lang ?? vn_lang();
	$mode = vn_is_content_preview() ? 'draft' : 'live';
	if ( ! isset( $cache[ $mode ] ) ) {
		$cache[ $mode ] = vn_content_store( $mode );
	}
	return (string) ( $cache[ $mode ]['fields'][ $lang ][ $key ] ?? '' );
}

/** Whether a page's Arabic version is published (ready flag + non-empty headline). */
function vn_page_ar_ready( $page ) {
	static $store = null;
	if ( null === $store || ! empty( $GLOBALS['vn_content_dirty_ar'] ) ) {
		$store = vn_content_store();
		unset( $GLOBALS['vn_content_dirty_ar'] );
	}
	if ( empty( $store['ar_ready'][ $page ] ) ) {
		return false;
	}
	$probe = array(
		'home'      => 'home.hero_title',
		'about'     => 'about.title',
		'services'  => 'services.title',
		'portfolio' => 'portfolio.title',
		'careers'   => 'careers.title',
		'contact'   => 'contact.title',
		'quote'     => 'quote.title',
		'privacy'   => 'privacy.body',
	);
	return ! isset( $probe[ $page ] ) || '' !== trim( (string) ( $store['fields']['ar'][ $probe[ $page ] ] ?? '' ) );
}

/** Enabled navigation items: [route, label, url]. */
function vn_nav_items( $lang = null ) {
	$lang  = $lang ?? vn_lang();
	$items = array();
	foreach ( vn_content_store()['nav'] as $item ) {
		if ( empty( $item['on'] ) || ! isset( vn_routes()[ $item['route'] ] ) ) {
			continue;
		}
		if ( 'ar' === $lang && ! vn_page_ar_ready( $item['route'] ) ) {
			continue;
		}
		$items[] = array(
			'route' => $item['route'],
			'label' => vn_c( 'global.nav_' . $item['route'], $lang ),
			'url'   => vn_url( $item['route'], $lang ),
		);
	}
	return $items;
}

/** Enabled homepage sections in order. */
function vn_home_sections() {
	return array_values(
		array_map(
			fn( $s ) => $s['id'],
			array_filter( vn_content_store()['home_sections'], fn( $s ) => ! empty( $s['on'] ) )
		)
	);
}
