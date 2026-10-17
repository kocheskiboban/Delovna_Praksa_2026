# -*- coding: utf-8 -*-
"""Demo content for the Ohrid Summer Festival platform.

Historical facts about the festival (founded 4 August 1961, St. Sophia as first stage,
EFA member since 1994, Antique Theatre as main stage since 2001, July 12 – August 20)
are real. The programme, performers, staff and sales are FICTIONAL DEMO DATA.
"""

LANGS = ["mk", "en", "sq", "de", "fr", "it"]

CATEGORIES = [
    # slug, icon, color, {lang: name}
    ("music", "music", "#134a66", {"mk": "Музика", "en": "Music", "sq": "Muzikë", "de": "Musik", "fr": "Musique", "it": "Musica"}),
    ("theatre", "mask", "#a8432f", {"mk": "Театар", "en": "Theatre", "sq": "Teatër", "de": "Theater", "fr": "Théâtre", "it": "Teatro"}),
    ("ballet", "ballet", "#7a4c8c", {"mk": "Балет", "en": "Ballet", "sq": "Balet", "de": "Ballett", "fr": "Ballet", "it": "Balletto"}),
    ("opera", "opera", "#8a6420", {"mk": "Опера", "en": "Opera", "sq": "Operë", "de": "Oper", "fr": "Opéra", "it": "Opera"}),
    ("literature", "book", "#4f6b3a", {"mk": "Литература", "en": "Literature", "sq": "Letërsi", "de": "Literatur", "fr": "Littérature", "it": "Letteratura"}),
    ("dance", "dance", "#b0547a", {"mk": "Танц", "en": "Dance", "sq": "Vallëzim", "de": "Tanz", "fr": "Danse", "it": "Danza"}),
    ("film", "film", "#2f4858", {"mk": "Филм", "en": "Film", "sq": "Film", "de": "Film", "fr": "Cinéma", "it": "Cinema"}),
    ("other", "spark", "#5c6770", {"mk": "Друго", "en": "Other", "sq": "Të tjera", "de": "Sonstiges", "fr": "Autres", "it": "Altro"}),
]

TICKET_TYPES = [
    # code, discount %, {lang: (name, description)}
    ("adult", 0, {
        "mk": ("Редовен", "Стандарден влез за возрасни посетители."),
        "en": ("Adult", "Standard admission for adult visitors."),
        "sq": ("I rregullt", "Hyrje standarde për vizitorë të rritur."),
        "de": ("Erwachsene", "Regulärer Eintritt für Erwachsene."),
        "fr": ("Adulte", "Entrée standard pour les adultes."),
        "it": ("Intero", "Ingresso standard per adulti."),
    }),
    ("student", 30, {
        "mk": ("Студентски", "−30% со важечки студентски индекс при влез."),
        "en": ("Student", "−30% with a valid student ID shown at the entrance."),
        "sq": ("Studentor", "−30% me kartë studenti të vlefshme në hyrje."),
        "de": ("Studierende", "−30 % mit gültigem Studierendenausweis am Einlass."),
        "fr": ("Étudiant", "−30 % sur présentation d'une carte étudiante valide."),
        "it": ("Studenti", "−30% con tessera universitaria valida all'ingresso."),
    }),
    ("senior", 20, {
        "mk": ("Пензионерски (65+)", "−20% за посетители над 65 години."),
        "en": ("Senior (65+)", "−20% for visitors aged 65 and over."),
        "sq": ("Pensionist (65+)", "−20% për vizitorë mbi 65 vjeç."),
        "de": ("Senioren (65+)", "−20 % für Besucher ab 65 Jahren."),
        "fr": ("Senior (65+)", "−20 % pour les visiteurs de 65 ans et plus."),
        "it": ("Over 65", "−20% per visitatori dai 65 anni in su."),
    }),
    ("child", 50, {
        "mk": ("Детски (до 14)", "−50% за деца до 14 години во придружба на возрасен."),
        "en": ("Child (under 14)", "−50% for children under 14 accompanied by an adult."),
        "sq": ("Fëmijë (nën 14)", "−50% për fëmijë nën 14 vjeç të shoqëruar nga një i rritur."),
        "de": ("Kinder (unter 14)", "−50 % für Kinder unter 14 in Begleitung Erwachsener."),
        "fr": ("Enfant (moins de 14 ans)", "−50 % pour les enfants de moins de 14 ans accompagnés."),
        "it": ("Bambini (under 14)", "−50% per bambini sotto i 14 anni accompagnati."),
    }),
]

# Venues — real places in Ohrid. Seat counts describe the festival's demo seating plans.
VENUES = [
    {
        "slug": "antique-theatre", "layout": "amphitheatre", "seating": "seated",
        "lat": 41.11469, "lng": 20.79688, "map": (41, 30), "address": "ул. Илинденска, Стар град, Охрид 6000",
        "address_en": "Ilindenska St., Old Town, Ohrid 6000",
        "mk": {
            "name": "Антички театар",
            "short": "Хеленистичкиот театар во срцето на Стариот град — главна сцена на фестивалот од 2001 година.",
            "desc": "Античкиот театар во Охрид е изграден околу 200 година пред нашата ера и е единствениот театар од хеленистичкиот период во Македонија. Сместен меѓу Горната порта и Самоиловата тврдина, тој нуди поглед кон езерото што ја прави секоја вечер посебна.\n\nОд 2001 година театарот е главна сцена на Охридско лето: тука се одржуваат свеченото отворање, оперските и балетските продукции и големите оркестарски концерти. Акустиката на камените скалила е извонредна и за глас и за оркестар.",
            "access": "Пристапот за посетители во инвалидска количка е од горниот влез (ул. Илинденска), со резервирани места во првиот ред. Скалилата се камени и нерамни — препорачуваме удобни обувки. Перничиња за седење се изнајмуваат на влезот.",
            "seating": "Нумерирани места на камените скалила, поделени во три сектори (лев, централен, десен). VIP-местата се во првите редови на централниот сектор.",
        },
        "en": {
            "name": "Antique Theatre",
            "short": "The Hellenistic theatre in the heart of the Old Town — the festival's main stage since 2001.",
            "desc": "Ohrid's Antique Theatre was built around 200 BC and is the only theatre from the Hellenistic period in North Macedonia. Set between the Upper Gate and Samuel's Fortress, it looks out over the lake, which makes every evening here memorable.\n\nSince 2001 the theatre has been the main stage of Ohrid Summer: the opening ceremony, opera and ballet productions and the large orchestral concerts all take place here. The acoustics of the stone tiers suit both voice and orchestra remarkably well.",
            "access": "Wheelchair access is through the upper entrance (Ilindenska St.), with reserved spaces in the first row. The stone tiers are uneven — comfortable shoes are recommended. Seat cushions can be rented at the entrance.",
            "seating": "Numbered seats on the stone tiers, in three sections (left, centre, right). VIP seats are in the front rows of the centre section.",
        },
    },
    {
        "slug": "st-sophia", "layout": "nave", "seating": "seated",
        "lat": 41.11222, "lng": 20.79356, "map": (38, 50), "address": "ул. Цар Самоил, Охрид 6000",
        "address_en": "Car Samoil St., Ohrid 6000",
        "mk": {
            "name": "Црква „Св. Софија“",
            "short": "Катедралната црква од XI век каде што на 4 август 1961 година се одржа првиот концерт на фестивалот.",
            "desc": "Соборниот храм „Св. Софија“ е еден од најзначајните споменици на средновековната уметност во Европа, со фрески од XI век. Токму тука, на 4 август 1961 година, се одржа концертот со кој започна Охридско лето.\n\nДенес црквата е домот на камерната музика, рециталите и хорската музика. Природната резонанца на просторот бара внимателно избран репертоар — и затоа концертите во „Св. Софија“ се меѓу најбараните во програмата.",
            "access": "Главниот влез има мала рампа. Внатре подот е рамен. Обезбедени се две места за инвалидски колички во последниот ред. Фотографирањето со блиц не е дозволено поради заштитата на фреските.",
            "seating": "Нумерирани столчиња во наосот, поделени со централна патека. Некои места зад столбовите не се продаваат поради ограничена видливост.",
        },
        "en": {
            "name": "Church of St. Sophia",
            "short": "The 11th-century cathedral where the festival's very first concert took place on 4 August 1961.",
            "desc": "The Cathedral of St. Sophia is one of the most important monuments of medieval art in Europe, with frescoes from the 11th century. It was here, on 4 August 1961, that the concert which began Ohrid Summer took place.\n\nToday the church is the home of chamber music, recitals and choral music. The natural resonance of the space calls for carefully chosen repertoire — which is why concerts at St. Sophia are among the most sought-after in the programme.",
            "access": "The main entrance has a small ramp and the floor inside is level. Two wheelchair spaces are provided in the last row. Flash photography is not allowed in order to protect the frescoes.",
            "seating": "Numbered chairs in the nave, divided by a central aisle. Some seats behind the pillars are not sold due to restricted view.",
        },
    },
    {
        "slug": "st-sophia-atrium", "layout": "courtyard", "seating": "seated",
        "lat": 41.11195, "lng": 20.79330, "map": (27, 60), "address": "ул. Цар Самоил, Охрид 6000",
        "address_en": "Car Samoil St., Ohrid 6000",
        "mk": {
            "name": "Предворје на „Св. Софија“",
            "short": "Летна сцена на отворено пред катедралата, за солисти, поезија и вечери под ѕвездите.",
            "desc": "Предворјето на „Св. Софија“ е интимна сцена на отворено, засенета со стари чинари. Во летните вечери тука се одржуваат солистички концерти, литературни читања и мали театарски форми.\n\nБлизината меѓу изведувачите и публиката, звуците на градот што стивнува и светлината на фасадата на катедралата создаваат атмосфера карактеристична само за Охрид.",
            "access": "Целосно рамен пристап од ул. Цар Самоил. Резервирани места за посетители со намалена подвижност во првиот ред.",
            "seating": "Нумерирани столчиња во благ лак, поделени во два блока. Во случај на дожд програмата се преместува во црквата.",
        },
        "en": {
            "name": "St. Sophia Atrium",
            "short": "An open-air stage in front of the cathedral for soloists, poetry and evenings under the stars.",
            "desc": "The atrium of St. Sophia is an intimate open-air stage shaded by old plane trees. On summer evenings it hosts solo concerts, literary readings and small-scale theatre.\n\nThe closeness between performers and audience, the sounds of the town settling down and the light on the cathedral façade create an atmosphere found only in Ohrid.",
            "access": "Fully level access from Car Samoil St. Reserved spaces for visitors with reduced mobility in the first row.",
            "seating": "Numbered chairs in a gentle arc, in two blocks. In case of rain the performance moves into the church.",
        },
    },
    {
        "slug": "dolni-saraj", "layout": "terrace", "seating": "seated",
        "lat": 41.11129, "lng": 20.79215, "map": (15, 69), "address": "Долни Сарај, Стар град, Охрид 6000",
        "address_en": "Dolni Saraj, Old Town, Ohrid 6000",
        "mk": {
            "name": "Долни Сарај",
            "short": "Тераса над езерото, отворена за џез, филм и современи програми.",
            "desc": "Долни Сарај е плато над самиот брег на езерото, помеѓу „Св. Софија“ и Каневе. Со поглед кон водата и залезот, оваа сцена е омилено место за џез-концертите, филмските проекции на отворено и современите танцови изведби.\n\nТерасите се поставени амфитеатрално, па и од последните редови има добар поглед кон сцената и езерото зад неа.",
            "access": "Пристап по калдрма од крајбрежната патека; за посетители со намалена подвижност е обезбеден превоз со електрично возило од пристаништето (по најава).",
            "seating": "Нумерирани места на дрвени тераси. VIP-местата се на првата тераса, најблиску до сцената.",
        },
        "en": {
            "name": "Dolni Saraj",
            "short": "A terrace above the lake, open to jazz, film and contemporary programmes.",
            "desc": "Dolni Saraj is a plateau right above the lakeshore, between St. Sophia and Kaneo. Facing the water and the sunset, it is a favourite stage for jazz concerts, open-air film screenings and contemporary dance.\n\nThe terraces are laid out like an amphitheatre, so even the back rows have a good view of the stage and the lake behind it.",
            "access": "Access is along the cobbled lakeside path; for visitors with reduced mobility an electric shuttle runs from the harbour (on request).",
            "seating": "Numbered seats on wooden terraces. VIP seats are on the first terrace, closest to the stage.",
        },
    },
    {
        "slug": "grigor-prlichev-centre", "layout": "hall", "seating": "seated",
        "lat": 41.11712, "lng": 20.80168, "map": (67, 24), "address": "ул. Македонски просветители бб, Охрид 6000",
        "address_en": "Makedonski Prosvetiteli St., Ohrid 6000",
        "mk": {
            "name": "ЦЗК „Григор Прличев“",
            "short": "Затворена сала со партер и балкон — дом на театарските гостувања и вонсезонските програми.",
            "desc": "Центарот за култура „Григор Прличев“ го носи името на охридскиот поет и преродбеник. Неговата голема сала со партер и балкон е затворената сцена на фестивалот: тука се играат театарските гостувања, танцовите претстави и концертите од вонсезонската програма „Прелудиум“.\n\nСалата е климатизирана, со професионално осветлување и надписи за претставите на странски јазици.",
            "access": "Лифт до балконот, рампа на главниот влез, прилагоден тоалет и индуктивна јамка за посетители со слушни апарати.",
            "seating": "Партер со 16 реда и балкон со 4 реда. VIP-местата се во редовите 5–8 во средината на партерот, каде што звукот е најдобар.",
        },
        "en": {
            "name": "Grigor Prlichev Cultural Centre",
            "short": "An indoor hall with stalls and balcony — home of visiting theatre and the off-season programme.",
            "desc": "The Grigor Prlichev Cultural Centre bears the name of the Ohrid poet and figure of the national revival. Its large hall with stalls and balcony is the festival's indoor stage: visiting theatre companies, dance performances and the off-season Prelude concerts take place here.\n\nThe hall is air-conditioned, with professional lighting and surtitles for performances in foreign languages.",
            "access": "Lift to the balcony, ramp at the main entrance, accessible toilet and an induction loop for visitors with hearing aids.",
            "seating": "Stalls with 16 rows and a balcony with 4 rows. VIP seats are in rows 5–8 in the middle of the stalls, where the sound is best.",
        },
    },
    {
        "slug": "plaoshnik", "layout": "open", "seating": "general", "capacity": 800,
        "lat": 41.11147, "lng": 20.78950, "map": (13, 44), "address": "Плаошник, Охрид 6000",
        "address_en": "Plaoshnik, Ohrid 6000",
        "mk": {
            "name": "Плаошник",
            "short": "Археолошкиот локалитет на Св. Климент — сцена на отворено без нумерирани места.",
            "desc": "Плаошник е местото каде што Св. Климент Охридски ја основал првата словенска книжевна школа. Ливадата пред обновената црква „Св. Пантелејмон“ се претвора во сцена на отворено за поголеми концерти без нумерирани места — фестивални вечери за сите генерации.\n\nВлезот е по стоечки/слободен распоред; донесете ќебе или перниче, а оние што сакаат да седат можат да дојдат порано.",
            "access": "Пристап по асфалтиран пат од Горна порта; одреден простор за посетители во инвалидски колички пред сцената.",
            "seating": "Слободен распоред (без нумерирани места). Капацитетот е ограничен на 800 посетители.",
        },
        "en": {
            "name": "Plaoshnik",
            "short": "The archaeological site of St. Clement — an open-air stage with general admission.",
            "desc": "Plaoshnik is where St. Clement of Ohrid founded the first Slavic literary school. The meadow in front of the restored church of St. Panteleimon becomes an open-air stage for larger concerts without numbered seating — festival evenings for every generation.\n\nAdmission is general (standing/unreserved); bring a blanket or cushion, and come early if you would like to sit.",
            "access": "Paved access road from the Upper Gate; a dedicated area for wheelchair users in front of the stage.",
            "seating": "General admission (no numbered seats). Capacity is limited to 800 visitors.",
        },
    },
]

EDITIONS = [
    # year, number, start, end, visitors, countries, {lang: (theme, summary)}
    (2024, 64, "2024-07-12", "2024-08-20", 41000, 23, {
        "mk": ("Гласови на езерото", "64. издание ги стави во фокус гласот и хорската традиција, со вечери посветени на византиското пеење и на оперската арија."),
        "en": ("Voices of the Lake", "The 64th edition focused on the voice and the choral tradition, with evenings devoted to Byzantine chant and the operatic aria."),
    }),
    (2025, 65, "2025-07-12", "2025-08-20", 44500, 26, {
        "mk": ("Мостови", "Јубилејното 65. издание ги поврза Балканот и Европа преку копродукции, млади уметници и нова сцена на Плаошник."),
        "en": ("Bridges", "The 65th anniversary edition connected the Balkans and Europe through co-productions, young artists and a new stage at Plaoshnik."),
    }),
    (2026, 66, "2026-07-12", "2026-08-20", 0, 24, {
        "mk": ("66. Охридско лето", "40 дена, 48 настани и над 700 уметници од 24 земји — од оперската гала „Љубов“ во Античкиот театар до мјузиклот „Као какао“ на Долни Сарај."),
        "en": ("66th Ohrid Summer", "40 days, 48 events and more than 700 artists from 24 countries — from the opera gala “Love” at the Antique Theatre to the musical “Kao kakao” at Dolni Saraj."),
    }),
    (2027, 67, "2027-07-12", "2027-08-20", 0, 0, {
        "mk": ("Хоризонти", "67. издание ги отвора хоризонтите: 17 вечери на шест сцени, со солисти, ансамбли и продукции од десет земји."),
        "en": ("Horizons", "The 67th edition opens new horizons: seventeen evenings on six stages, with soloists, ensembles and productions from ten countries."),
    }),
]

# Fictional artists (demo data).
ARTISTS = [
    {"slug": "ohrid-festival-orchestra", "cat": "music", "country": "MK", "featured": 1, "ensemble": 1,
     "web": "https://example.org/ohrid-festival-orchestra", "ig": "ohridfestivalorchestra",
     "mk": ("Оркестар на Охридско лето", "Симфониски оркестар", "Оркестарот на Охридско лето е резидентен ансамбл што секое лето собира околу седумдесет музичари од македонските оркестри и од дијаспората. Ансамблот ги изведува свеченото отворање и затворање на фестивалот, оперските продукции и големите симфониски дела.\n\nВо последните сезони оркестарот посебно внимание посветува на македонските композитори, со праизведби на дела нарачани од фестивалот."),
     "en": ("Ohrid Festival Orchestra", "Symphony orchestra", "The Ohrid Festival Orchestra is the resident ensemble that each summer brings together some seventy musicians from Macedonian orchestras and the diaspora. It performs the festival's opening and closing ceremonies, the opera productions and the large symphonic works.\n\nIn recent seasons the orchestra has paid special attention to Macedonian composers, with premieres of works commissioned by the festival.")},
    {"slug": "elena-markovska", "cat": "music", "country": "MK", "featured": 1,
     "web": "https://example.org/elena-markovska", "ig": "elenamarkovska.piano",
     "mk": ("Елена Марковска", "Пијанистка", "Елена Марковска студирала во Скопје и Виена, а денес настапува како солистка и камерна музичарка низ Европа. Нејзиниот репертоар се движи од Шуберт и Шопен до македонската клавирска литература, која ја снима за серијата „Охридски тетратки“.\n\nНа Охридско лето настапува редовно од 2019 година, а од 2025 води и мајсторски клас за млади пијанисти."),
     "en": ("Elena Markovska", "Pianist", "Elena Markovska studied in Skopje and Vienna and today performs as a soloist and chamber musician across Europe. Her repertoire ranges from Schubert and Chopin to Macedonian piano literature, which she is recording for the series 'Ohrid Notebooks'.\n\nShe has appeared regularly at Ohrid Summer since 2019 and has led a masterclass for young pianists since 2025.")},
    {"slug": "luca-ferraresi", "cat": "music", "country": "IT", "featured": 1,
     "web": "https://example.org/luca-ferraresi",
     "mk": ("Лука Ферарези", "Диригент", "Италијанскиот диригент Лука Ферарези е познат по своите интерпретации на Верди и Пучини и по работата со млади оркестри. Студирал во Милано, а подоцна бил асистент во неколку европски оперски куќи.\n\nНа Охридско лето го диригира свеченото отворање и оперската продукција на фестивалот, во соработка со Оркестарот на Охридско лето."),
     "en": ("Luca Ferraresi", "Conductor", "Italian conductor Luca Ferraresi is known for his interpretations of Verdi and Puccini and for his work with youth orchestras. He studied in Milan and later served as an assistant at several European opera houses.\n\nAt Ohrid Summer he conducts the opening gala and the festival's opera production with the Ohrid Festival Orchestra.")},
    {"slug": "marie-claire-dubois", "cat": "opera", "country": "FR", "featured": 1,
     "web": "https://example.org/marie-claire-dubois", "ig": "mcdubois.soprano",
     "mk": ("Мари-Клер Дибоа", "Сопран", "Француската сопранистка Мари-Клер Дибоа ја гради кариерата на лирскиот репертоар — од Моцарт до Верди и Масне. Критиката го истакнува нејзиниот топол тон и драматската убедливост.\n\nВо 2027 година ја пее Виолета во концертната поставка на „Травијата“ во Античкиот театар."),
     "en": ("Marie-Claire Dubois", "Soprano", "French soprano Marie-Claire Dubois has built her career on the lyric repertoire — from Mozart to Verdi and Massenet. Critics praise her warm tone and dramatic conviction.\n\nIn 2027 she sings Violetta in the concert staging of La traviata at the Antique Theatre.")},
    {"slug": "jonas-albrecht", "cat": "music", "country": "DE", "featured": 0,
     "web": "https://example.org/jonas-albrecht",
     "mk": ("Јонас Албрехт", "Виолончелист", "Германскиот виолончелист Јонас Албрехт е посветен на музиката на Бах и на современите композитори. Свири на инструмент од 1740 година, позајмен од фондација за млади уметници.\n\nНеговиот интегрален циклус на Баховите свити за соло виолончело е снимен во црквите на неколку градови по патот на Via Egnatia."),
     "en": ("Jonas Albrecht", "Cellist", "German cellist Jonas Albrecht is devoted to the music of Bach and to contemporary composers. He plays an instrument from 1740 on loan from a foundation for young artists.\n\nHis complete cycle of Bach's suites for solo cello was recorded in churches of several towns along the Via Egnatia.")},
    {"slug": "arta-krasniqi", "cat": "music", "country": "AL", "featured": 1,
     "web": "https://example.org/arta-krasniqi", "ig": "arta.violin",
     "mk": ("Арта Краснићи", "Виолинистка", "Арта Краснићи е родена во Тирана и школувана во Лондон. Победничка е на неколку меѓународни натпревари, а нејзиниот прв албум со концертите на Сибелиус и Прокофјев беше добро примен од критиката.\n\nВо Охрид настапува како солистка со Оркестарот на Охридско лето."),
     "en": ("Arta Krasniqi", "Violinist", "Arta Krasniqi was born in Tirana and trained in London. She has won several international competitions, and her debut album of the Sibelius and Prokofiev concertos was warmly received by critics.\n\nIn Ohrid she appears as soloist with the Ohrid Festival Orchestra.")},
    {"slug": "teodor-ilievski", "cat": "theatre", "country": "MK", "featured": 1,
     "web": "https://example.org/teodor-ilievski",
     "mk": ("Теодор Илиевски", "Театарски режисер", "Теодор Илиевски е режисер чии претстави ги поврзуваат античката драма и современиот сценски јазик. Неговите продукции гостувале на фестивали во Грција, Србија и Хрватска.\n\nЗа Охридско лето ја режира „Медеја“ на Еврипид, создадена специјално за просторот на Античкиот театар."),
     "en": ("Teodor Ilievski", "Theatre director", "Teodor Ilievski is a director whose productions connect ancient drama with a contemporary stage language. His work has toured festivals in Greece, Serbia and Croatia.\n\nFor Ohrid Summer he directs Euripides' Medea, created specifically for the space of the Antique Theatre.")},
    {"slug": "balkan-brass-collective", "cat": "music", "country": "RS", "featured": 0, "ensemble": 1,
     "web": "https://example.org/balkan-brass-collective", "ig": "balkanbrasscollective",
     "mk": ("Балкан брас колектив", "Дувачки оркестар", "Дванаесет музичари од Србија, Македонија и Бугарија кои балканските трубачки традиции ги спојуваат со џез и фанк. Нивните концерти се славни по енергијата — ретко кој останува да седи.\n\nНа Плаошник ќе ја изведат својата нова програма „Езерски марш“."),
     "en": ("Balkan Brass Collective", "Brass band", "Twelve musicians from Serbia, North Macedonia and Bulgaria who fuse Balkan brass traditions with jazz and funk. Their concerts are famous for their energy — hardly anyone stays seated.\n\nAt Plaoshnik they present their new programme 'Lake March'.")},
    {"slug": "lychnidos-ballet", "cat": "ballet", "country": "MK", "featured": 1, "ensemble": 1,
     "web": "https://example.org/lychnidos-ballet",
     "mk": ("Балет Лихнидос", "Балетска компанија", "Балет Лихнидос е фестивална компанија формирана во 2023 година, која собира солисти од регионот и млади танчери од балетските училишта. Името го носи античкото име на Охрид.\n\nНивната нова продукција „Езеро од светлина“ е инспирирана од легендите за езерото."),
     "en": ("Lychnidos Ballet", "Ballet company", "Lychnidos Ballet is a festival company formed in 2023 that brings together soloists from the region and young dancers from ballet schools. It takes its name from the ancient name of Ohrid.\n\nIts new production 'Lake of Light' is inspired by legends of the lake.")},
    {"slug": "sofia-andreou", "cat": "opera", "country": "GR", "featured": 0,
     "web": "https://example.org/sofia-andreou",
     "mk": ("Софија Андреу", "Мецосопран", "Грчката мецосопранистка Софија Андреу е подеднакво дома во ораториумот и во операта. Студирала во Солун и Минхен.\n\nНа Охридско лето настапува во Деветтата симфонија на Бетовен и во Реквиемот на Моцарт."),
     "en": ("Sofia Andreou", "Mezzo-soprano", "Greek mezzo-soprano Sofia Andreou is equally at home in oratorio and opera. She studied in Thessaloniki and Munich.\n\nAt Ohrid Summer she appears in Beethoven's Ninth Symphony and Mozart's Requiem.")},
    {"slug": "nadia-kowalska", "cat": "dance", "country": "PL", "featured": 0,
     "web": "https://example.org/nadia-kowalska",
     "mk": ("Наѓа Ковалска", "Кореографка", "Полската кореографка Наѓа Ковалска создава современи танцови претстави за мали ансамбли, често во соработка со визуелни уметници. Нејзиниот јазик е прецизен и поетичен.\n\nПретставата „Прагови“ е за пет танчери и жива електронска музика."),
     "en": ("Nadia Kowalska", "Choreographer", "Polish choreographer Nadia Kowalska creates contemporary dance pieces for small ensembles, often in collaboration with visual artists. Her language is precise and poetic.\n\n'Thresholds' is a piece for five dancers and live electronics.")},
    {"slug": "hana-sato", "cat": "music", "country": "JP", "featured": 0,
     "web": "https://example.org/hana-sato",
     "mk": ("Хана Сато", "Виолинистка", "Хана Сато е родена во Осака, а живее во Берлин. Позната е по камерната музика и по долгогодишното дуо со пијанистката Елена Марковска.\n\nНивната програма во Охрид ги обединува трите сонати за виолина и пијано од Брамс."),
     "en": ("Hana Sato", "Violinist", "Hana Sato was born in Osaka and lives in Berlin. She is known for her chamber music and for her long-standing duo with pianist Elena Markovska.\n\nTheir Ohrid programme brings together Brahms's three sonatas for violin and piano.")},
    {"slug": "aleksandar-trajkov", "cat": "literature", "country": "MK", "featured": 0,
     "web": "https://example.org/aleksandar-trajkov",
     "mk": ("Александар Трајков", "Поет", "Александар Трајков е поет и преведувач, автор на пет збирки поезија. Неговите стихови се преведени на неколку европски јазици.\n\nНа фестивалот ја води вечерта „Поезија крај езерото“ со гости од регионот."),
     "en": ("Aleksandar Trajkov", "Poet", "Aleksandar Trajkov is a poet and translator, the author of five poetry collections. His work has been translated into several European languages.\n\nAt the festival he hosts the evening 'Poetry by the Lake' with guests from the region.")},
    {"slug": "quartetto-lago", "cat": "music", "country": "IT", "featured": 0, "ensemble": 1,
     "web": "https://example.org/quartetto-lago",
     "mk": ("Куартето Лаго", "Гудачки квартет", "Квартетот е основан во Комо во 2016 година. Репертоарот им се движи од Хајдн до Шостакович и современите италијански композитори.\n\nВо „Св. Софија“ ќе изведат програма што ги поврзува класичниот и модерниот квартет."),
     "en": ("Quartetto Lago", "String quartet", "The quartet was founded in Como in 2016. Its repertoire ranges from Haydn to Shostakovich and contemporary Italian composers.\n\nAt St. Sophia they present a programme connecting the classical and the modern quartet.")},
    {"slug": "mira-stojanova", "cat": "film", "country": "MK", "featured": 0,
     "web": "https://example.org/mira-stojanova",
     "mk": ("Мира Стојанова", "Филмска режисерка", "Мира Стојанова е документаристка чии филмови се занимаваат со водата, сеќавањето и малите заедници. Нејзиниот филм „Меморија на водата“ е снимен во текот на четири години покрај Охридското Езеро.\n\nПо проекцијата следува разговор со авторката."),
     "en": ("Mira Stojanova", "Film director", "Mira Stojanova is a documentary filmmaker whose films deal with water, memory and small communities. Her film 'Water Memory' was shot over four years along Lake Ohrid.\n\nThe screening is followed by a conversation with the director.")},
    {"slug": "ensemble-byzantion", "cat": "music", "country": "MK", "featured": 0, "ensemble": 1,
     "web": "https://example.org/ensemble-byzantion",
     "mk": ("Ансамбл Византион", "Хор за рана музика", "Машкиот вокален ансамбл Византион ја истражува византиската и старословенската црковна музика, работејќи со ракописи од охридските збирки.\n\nНивните концерти во „Св. Софија“ се враќаат на звукот за кој овој простор бил изграден."),
     "en": ("Ensemble Byzantion", "Early music choir", "The male vocal ensemble Byzantion explores Byzantine and Old Church Slavonic sacred music, working from manuscripts in the Ohrid collections.\n\nTheir concerts at St. Sophia return to the sound for which the space was built.")},
    {"slug": "daniel-okafor", "cat": "music", "country": "GB", "featured": 0,
     "web": "https://example.org/daniel-okafor", "ig": "danielokafortrio",
     "mk": ("Даниел Окафор", "Џез пијанист", "Британскиот пијанист Даниел Окафор води трио што ги спојува џезот, госпелот и западноафриканските ритми. Неговите настапи се спонтани и полни со импровизација.\n\nНа Долни Сарај свири со тријото на заоѓање на сонцето."),
     "en": ("Daniel Okafor", "Jazz pianist", "British pianist Daniel Okafor leads a trio that blends jazz, gospel and West African rhythms. His performances are spontaneous and full of improvisation.\n\nAt Dolni Saraj he plays with his trio at sunset.")},
    {"slug": "teatro-dellacqua", "cat": "theatre", "country": "IT", "featured": 0, "ensemble": 1,
     "web": "https://example.org/teatro-dellacqua",
     "mk": ("Компанија Театро делАква", "Театарска компанија", "Независна театарска компанија од Венеција што работи на границата меѓу драмата, движењето и музиката. Нивните претстави патуваат по медитеранските фестивали.\n\nВо Охрид ја играат „Морето внатре“ — на италијански, со македонски и англиски надписи."),
     "en": ("Compagnia Teatro dell'Acqua", "Theatre company", "An independent theatre company from Venice working at the border of drama, movement and music. Their productions tour Mediterranean festivals.\n\nIn Ohrid they perform 'The Sea Within' — in Italian, with Macedonian and English surtitles.")},
]
