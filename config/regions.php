<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| استان‌ها و شهرها (DEC-60)
|--------------------------------------------------------------------------
|
| فهرست ثابت ۳۱ استان و شهرهای مهم هر کدام. کلید لاتین در نشانی صفحه‌ها
| می‌آید (دایرکتوری بخش ۱۹-۵: `/directory/{خدمت}/{شهر}`)، پس کلید شهر در
| کل فهرست یکتاست و پس از انتشار عوض نمی‌شود. شهر تازه فقط به ته فهرست
| همان استان اضافه می‌شود.
|
*/

return [

    'tehran' => ['name' => 'تهران', 'cities' => [
        'tehran' => 'تهران', 'rey' => 'ری', 'shahriar' => 'شهریار', 'eslamshahr' => 'اسلامشهر',
        'varamin' => 'ورامین', 'pakdasht' => 'پاکدشت', 'damavand' => 'دماوند',
    ]],
    'alborz' => ['name' => 'البرز', 'cities' => [
        'karaj' => 'کرج', 'fardis' => 'فردیس', 'nazarabad' => 'نظرآباد', 'hashtgerd' => 'هشتگرد',
    ]],
    'isfahan' => ['name' => 'اصفهان', 'cities' => [
        'isfahan' => 'اصفهان', 'kashan' => 'کاشان', 'najafabad' => 'نجف‌آباد', 'khomeinishahr' => 'خمینی‌شهر',
        'shahinshahr' => 'شاهین‌شهر', 'mobarakeh' => 'مبارکه',
    ]],
    'fars' => ['name' => 'فارس', 'cities' => [
        'shiraz' => 'شیراز', 'marvdasht' => 'مرودشت', 'jahrom' => 'جهرم', 'lar' => 'لار',
        'kazerun' => 'کازرون', 'fasa' => 'فسا',
    ]],
    'khorasan-razavi' => ['name' => 'خراسان رضوی', 'cities' => [
        'mashhad' => 'مشهد', 'neyshabur' => 'نیشابور', 'sabzevar' => 'سبزوار',
        'torbat-heydarieh' => 'تربت حیدریه', 'quchan' => 'قوچان',
    ]],
    'khuzestan' => ['name' => 'خوزستان', 'cities' => [
        'ahvaz' => 'اهواز', 'abadan' => 'آبادان', 'khorramshahr' => 'خرمشهر', 'dezful' => 'دزفول',
        'bandar-mahshahr' => 'بندر ماهشهر', 'bandar-imam' => 'بندر امام خمینی',
        'masjed-soleyman' => 'مسجدسلیمان', 'behbahan' => 'بهبهان',
    ]],
    'east-azerbaijan' => ['name' => 'آذربایجان شرقی', 'cities' => [
        'tabriz' => 'تبریز', 'maragheh' => 'مراغه', 'marand' => 'مرند', 'ahar' => 'اهر',
    ]],
    'west-azerbaijan' => ['name' => 'آذربایجان غربی', 'cities' => [
        'urmia' => 'ارومیه', 'khoy' => 'خوی', 'mahabad' => 'مهاباد', 'miandoab' => 'میاندوآب',
    ]],
    'ardabil' => ['name' => 'اردبیل', 'cities' => [
        'ardabil' => 'اردبیل', 'parsabad' => 'پارس‌آباد', 'meshginshahr' => 'مشگین‌شهر',
    ]],
    'kermanshah' => ['name' => 'کرمانشاه', 'cities' => [
        'kermanshah' => 'کرمانشاه', 'eslamabad-gharb' => 'اسلام‌آباد غرب', 'kangavar' => 'کنگاور',
    ]],
    'kerman' => ['name' => 'کرمان', 'cities' => [
        'kerman' => 'کرمان', 'sirjan' => 'سیرجان', 'rafsanjan' => 'رفسنجان', 'bam' => 'بم', 'jiroft' => 'جیرفت',
    ]],
    'hormozgan' => ['name' => 'هرمزگان', 'cities' => [
        'bandar-abbas' => 'بندرعباس', 'qeshm' => 'قشم', 'kish' => 'کیش', 'bandar-lengeh' => 'بندر لنگه',
    ]],
    'bushehr' => ['name' => 'بوشهر', 'cities' => [
        'bushehr' => 'بوشهر', 'asaluyeh' => 'عسلویه', 'kangan' => 'کنگان', 'genaveh' => 'گناوه',
    ]],
    'gilan' => ['name' => 'گیلان', 'cities' => [
        'rasht' => 'رشت', 'bandar-anzali' => 'بندر انزلی', 'lahijan' => 'لاهیجان', 'langarud' => 'لنگرود',
    ]],
    'mazandaran' => ['name' => 'مازندران', 'cities' => [
        'sari' => 'ساری', 'babol' => 'بابل', 'amol' => 'آمل', 'qaemshahr' => 'قائم‌شهر',
        'behshahr' => 'بهشهر', 'nowshahr' => 'نوشهر',
    ]],
    'golestan' => ['name' => 'گلستان', 'cities' => [
        'gorgan' => 'گرگان', 'gonbad-kavus' => 'گنبد کاووس', 'aliabad-katul' => 'علی‌آباد کتول',
    ]],
    'semnan' => ['name' => 'سمنان', 'cities' => [
        'semnan' => 'سمنان', 'shahroud' => 'شاهرود', 'damghan' => 'دامغان', 'garmsar' => 'گرمسار',
    ]],
    'qom' => ['name' => 'قم', 'cities' => [
        'qom' => 'قم',
    ]],
    'qazvin' => ['name' => 'قزوین', 'cities' => [
        'qazvin' => 'قزوین', 'takestan' => 'تاکستان', 'abyek' => 'آبیک',
    ]],
    'markazi' => ['name' => 'مرکزی', 'cities' => [
        'arak' => 'اراک', 'saveh' => 'ساوه', 'khomein' => 'خمین', 'mahallat' => 'محلات',
    ]],
    'hamadan' => ['name' => 'همدان', 'cities' => [
        'hamadan' => 'همدان', 'malayer' => 'ملایر', 'nahavand' => 'نهاوند',
    ]],
    'zanjan' => ['name' => 'زنجان', 'cities' => [
        'zanjan' => 'زنجان', 'abhar' => 'ابهر', 'khodabandeh' => 'خدابنده',
    ]],
    'kurdistan' => ['name' => 'کردستان', 'cities' => [
        'sanandaj' => 'سنندج', 'saqqez' => 'سقز', 'marivan' => 'مریوان', 'baneh' => 'بانه',
    ]],
    'lorestan' => ['name' => 'لرستان', 'cities' => [
        'khorramabad' => 'خرم‌آباد', 'borujerd' => 'بروجرد', 'dorud' => 'دورود', 'aligudarz' => 'الیگودرز',
    ]],
    'ilam' => ['name' => 'ایلام', 'cities' => [
        'ilam' => 'ایلام', 'dehloran' => 'دهلران',
    ]],
    'chaharmahal-bakhtiari' => ['name' => 'چهارمحال و بختیاری', 'cities' => [
        'shahrekord' => 'شهرکرد', 'borujen' => 'بروجن',
    ]],
    'kohgiluyeh-boyerahmad' => ['name' => 'کهگیلویه و بویراحمد', 'cities' => [
        'yasuj' => 'یاسوج', 'gachsaran' => 'گچساران', 'dehdasht' => 'دهدشت',
    ]],
    'yazd' => ['name' => 'یزد', 'cities' => [
        'yazd' => 'یزد', 'ardakan' => 'اردکان', 'meybod' => 'میبد', 'bafq' => 'بافق',
    ]],
    'sistan-baluchestan' => ['name' => 'سیستان و بلوچستان', 'cities' => [
        'zahedan' => 'زاهدان', 'zabol' => 'زابل', 'chabahar' => 'چابهار', 'iranshahr' => 'ایرانشهر',
    ]],
    'south-khorasan' => ['name' => 'خراسان جنوبی', 'cities' => [
        'birjand' => 'بیرجند', 'tabas' => 'طبس', 'qaen' => 'قائن',
    ]],
    'north-khorasan' => ['name' => 'خراسان شمالی', 'cities' => [
        'bojnurd' => 'بجنورد', 'shirvan' => 'شیروان', 'esfarayen' => 'اسفراین',
    ]],

];
