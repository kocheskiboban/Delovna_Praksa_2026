# -*- coding: utf-8 -*-
"""Photographs used by the site (JPG, stored in assets/img/).

All photographs come from Unsplash (https://unsplash.com/license — free to use, no
attribution required; credits are listed anyway in docs/IMAGE-CREDITS.md).
Except where the caption names an Ohrid landmark, the photos are ILLUSTRATIVE: they show
concerts, theatre and dance in general, not the actual festival events or artists.

`PHOTOS` maps a photo file (relative to assets/img/) to its Unsplash id and captions.
"""

# --- shared photo pool for events and news -----------------------------------------
# name -> (unsplash id, mk caption, en caption)
POOL = {
    "orchestra-conductor-hall": ("8J3TaXShe-s", "Диригент и оркестар во концертна сала", "A conductor and orchestra in a concert hall"),
    "orchestra-and-choir": ("J30aQDLRb_o", "Оркестар и хор на сцена", "Orchestra and choir on stage"),
    "orchestra-conductor-stage": ("dDHhaQlfEm0", "Оркестар под диригентска палка", "An orchestra under the conductor's baton"),
    "orchestra-audience": ("p60mNTW5glI", "Публика пред симфониски концерт", "The audience at a symphony concert"),
    "orchestra-concert-hall": ("NsgsQjHA1mM", "Оркестар во историска сала", "An orchestra in a historic hall"),
    "orchestra-dim-stage": ("nnS5adiX5mA", "Оркестар во пригушено светло", "An orchestra in dimmed light"),
    "string-players": ("7CcPLtywRso", "Гудачи за време на концерт", "String players during a concert"),
    "violinists-close": ("slbOcNlWNHA", "Виолинисти одблизу", "Violinists up close"),
    "cellist": ("veCoBypas4A", "Виолончелист", "A cellist"),
    "cello-bow": ("DKwZf34F5dQ", "Гудало на виолончело", "Cello bow"),
    "violin-bw": ("rAfTkgvIYkA", "Виолина во црно-бело", "Violin in black and white"),
    "piano-hands-bw": ("FjElUqGfbAw", "Раце на пијано", "Hands on the piano"),
    "grand-piano-stage": ("35fycLrg6U8", "Концертен клавир на сцена", "A grand piano on stage"),
    "grand-piano-dark": ("Zu2P0CV7B2k", "Клавир во полумрак", "A piano in the half-light"),
    "piano-keys-hands": ("UGdwsAtRFHI", "Пијанист на клавијатурата", "A pianist at the keyboard"),
    "jazz-saxophones": ("dBWvUqBoOU8", "Саксофони во џез-бенд", "Saxophones in a jazz band"),
    "saxophone-silhouette": ("zAPbXrhIfTU", "Силуета на саксофонист", "Silhouette of a saxophonist"),
    "saxophonist-red-light": ("BY_KyTwTKq4", "Саксофонист во црвено светло", "A saxophonist in red light"),
    "saxophone-club": ("z_disLOcoKM", "Саксофон во џез-клуб", "Saxophone in a jazz club"),
    "trumpet-night": ("A10y2Eq7OHY", "Труба во ноќта", "Trumpet at night"),
    "jazz-band-stage": ("FU1KddSIIR4", "Џез-бенд на сцена", "A jazz band on stage"),
    "choir-church": ("ZaZcvU_WuF8", "Хор во црква", "A choir in a church"),
    "choir-dark": ("ZhTYr3YKgqQ", "Хор во темна сала", "A choir in a dark hall"),
    "harpist": ("KlgPfP3BJCU", "Харфистка", "A harpist"),
    "opera-house": ("GE-x38xCebQ", "Оперска сала", "An opera house"),
    "singer-spotlight": ("k4s5mTSYULI", "Пејачка во рефлектор", "A singer in the spotlight"),
    "performer-warm-light": ("GKx96wY_MoA", "Изведувачка во топло светло", "A performer in warm light"),
    "theatre-silhouettes": ("p6rNTdAPbuk", "Актери пред црвена завеса", "Actors before a red curtain"),
    "theatre-curtain": ("WW1jsInXgwM", "Театарска завеса", "A theatre curtain"),
    "stage-set-blue": ("jhPSJDhiRyQ", "Сценографија во сина светлина", "A stage set in blue light"),
    "solo-performer-stage": ("ub77xN37pNs", "Изведувач сам на сцената", "A performer alone on stage"),
    "ballerina-white": ("zCZzSK5yX5A", "Балерина во бел туту", "A ballerina in a white tutu"),
    "ballet-ensemble": ("nZU76qWy-T8", "Балетски ансамбл", "A ballet ensemble"),
    "ballet-dancer-bw": ("gbd6PqRqGms", "Балетска танчерка", "A ballet dancer"),
    "dancer-motion": ("KHipnBn7sdY", "Танчерка во движење", "A dancer in motion"),
    "dance-company": ("hFTcxZFsG6g", "Танцова трупа на сцена", "A dance company on stage"),
    "dancer-stage": ("LsMxdW1zWEQ", "Танчерка на сцена", "A dancer on stage"),
    "outdoor-cinema": ("w23ynvKCXlk", "Кино на отворено", "An open-air cinema"),
    "outdoor-cinema-chairs": ("Qp0L7yZP6kc", "Столчиња пред филмско платно", "Chairs before an outdoor screen"),
    "book-reading": ("zMRLZh40kms", "Читање книга", "Reading a book"),
    "sheet-music": ("rPOmLGwai2w", "Ноти", "Sheet music"),
    "brass-trumpets": ("HeWRZ5-5Kt0", "Дувачи", "Brass players"),
}

# category / keyword -> photo names (rotated so neighbouring events differ)
EVENT_POOLS = {
    "orchestra": ["orchestra-conductor-hall", "orchestra-and-choir", "orchestra-conductor-stage", "orchestra-concert-hall", "orchestra-dim-stage", "orchestra-audience"],
    "chamber": ["string-players", "violinists-close", "cellist", "cello-bow", "violin-bw"],
    "piano": ["piano-keys-hands", "grand-piano-stage", "piano-hands-bw", "grand-piano-dark"],
    "jazz": ["saxophonist-red-light", "jazz-band-stage", "trumpet-night", "saxophone-silhouette", "saxophone-club", "jazz-saxophones"],
    "choir": ["choir-church", "choir-dark"],
    "harp": ["harpist"],
    "opera": ["opera-house", "singer-spotlight", "performer-warm-light"],
    "theatre": ["theatre-silhouettes", "stage-set-blue", "theatre-curtain", "solo-performer-stage"],
    "ballet": ["ballet-ensemble", "ballerina-white", "ballet-dancer-bw"],
    "dance": ["dancer-motion", "dance-company", "dancer-stage"],
    "film": ["outdoor-cinema", "outdoor-cinema-chairs"],
    "literature": ["book-reading"],
    "other": ["sheet-music"],
    "brass": ["brass-trumpets"],
}

KEYWORDS = [  # first match wins
    ("jazz", "jazz"), ("okafor", "jazz"), ("brass", "brass"), ("harp", "harp"), ("sadikova", "harp"),
    ("byzantion", "choir"), ("gothic", "choir"), ("choir", "choir"),
    ("piano", "piano"), ("recital", "piano"), ("markovska", "piano"), ("melnikov", "piano"), ("kholodenko", "piano"),
    ("siheng", "piano"), ("kanneh", "piano"), ("pegoraro", "piano"), ("stojanovska", "piano"), ("birthday", "piano"),
    ("quartet", "chamber"), ("trio", "chamber"), ("vibrart", "chamber"), ("hermes", "chamber"), ("glam", "chamber"),
    ("baroque", "chamber"), ("slovak", "chamber"), ("dutch", "chamber"), ("greek", "chamber"), ("eu-evening", "chamber"),
    ("hungarian", "chamber"), ("albrecht", "chamber"), ("cello", "chamber"), ("altus", "chamber"), ("seminar", "chamber"),
    ("sato", "chamber"), ("krasniqi", "chamber"), ("milenkovic", "chamber"), ("signum", "chamber"), ("austrian", "piano"),
    ("monograph", "literature"),
]

CAT_DEFAULT = {"music": "orchestra", "opera": "opera", "theatre": "theatre", "ballet": "ballet", "dance": "dance",
               "film": "film", "literature": "literature", "other": "other"}

_counters = {}


def event_photo(slug, cat):
    """Path (relative to the site root) of the photo used for an event."""
    pool = next((p for k, p in KEYWORDS if k in slug), None) or CAT_DEFAULT.get(cat, "orchestra")
    names = EVENT_POOLS[pool]
    n = _counters.get(pool, 0)
    _counters[pool] = n + 1
    return f"assets/img/photos/{names[n % len(names)]}.jpg"


NEWS_PHOTOS = {
    "programme-67th-edition-announced": "orchestra-and-choir",
    "tickets-on-sale-early-bird": "orchestra-audience",
    "prelude-series-autumn-2026": "grand-piano-stage",
    "medea-rehearsals-begin": "stage-set-blue",
    "66th-edition-in-numbers": "orchestra-concert-hall",
    "st-sophia-1961-first-concert": "piano-hands-bw",
    "volunteers-call-2027": "theatre-curtain",
    "accessible-festival": "opera-house",
    "lake-of-light-costumes": "ballerina-white",
}


def news_photo(slug):
    return f"assets/img/photos/{NEWS_PHOTOS.get(slug, 'sheet-music')}.jpg"


# --- venues (real Ohrid photographs where available) -------------------------------
VENUES = {  # slug -> (unsplash id, note)
    "antique-theatre": ("EduuFaEqPXY", "Antique Theatre of Ohrid"),
    "st-sophia": ("K7KzgdURSSA", "Church of St. Sophia, Ohrid"),
    "st-sophia-atrium": ("K7KzgdURSSA", "Church of St. Sophia, Ohrid (detail)"),
    "dolni-saraj": ("o5vBHDJGJww", "Ohrid waterfront at dusk (illustrative for the Dolni Saraj stage)"),
    "grigor-prlichev-centre": ("dGKqti2r08k", "Theatre auditorium (illustrative)"),
    "plaoshnik": ("LOd9xJzw_ZY", "Plaoshnik — Church of Sts. Clement and Panteleimon, Ohrid"),
}

# --- artists and team: no identifiable faces (the people are fictional) ------------
ARTISTS = {
    "ohrid-festival-orchestra": "nnS5adiX5mA", "elena-markovska": "UGdwsAtRFHI", "luca-ferraresi": "8J3TaXShe-s",
    "marie-claire-dubois": "k4s5mTSYULI", "jonas-albrecht": "DKwZf34F5dQ", "arta-krasniqi": "rAfTkgvIYkA",
    "teodor-ilievski": "jhPSJDhiRyQ", "balkan-brass-collective": "HeWRZ5-5Kt0", "lychnidos-ballet": "nZU76qWy-T8",
    "sofia-andreou": "ub77xN37pNs", "nadia-kowalska": "KHipnBn7sdY", "hana-sato": "slbOcNlWNHA",
    "aleksandar-trajkov": "zMRLZh40kms", "quartetto-lago": "7CcPLtywRso", "mira-stojanova": "mxiSNRVQeXI",
    "ensemble-byzantion": "ZhTYr3YKgqQ", "daniel-okafor": "FU1KddSIIR4", "teatro-dellacqua": "p6rNTdAPbuk",
}
TEAM = {
    "ana.velkovska": "sSilT5JmPUE", "marko.ristevski": "W7ZLuQNhXR8", "jana.petreska": "WW1jsInXgwM",
    "dimitar.nikolov": "mn6DDkoBIss", "elena.taseva": "Y20JJ_ddy9M", "bojan.angelov": "pRh9RiynHGE",
    "lea.hoxha": "p-9_F3IafnI", "ivan.spasov": "p60mNTW5glI",
}

# --- gallery: album -> [(unsplash id, mk caption, en caption)] ---------------------
OHRID = "Охрид"
GALLERY = {
    "opening-night-2026": [
        ("p60mNTW5glI", "Публиката чека да започне концертот", "The audience waits for the concert to begin"),
        ("NsgsQjHA1mM", "Оркестарот на сцената", "The orchestra on stage"),
        ("nnS5adiX5mA", "Оркестар во пригушено светло", "An orchestra in dimmed light"),
        ("dDHhaQlfEm0", "Диригентот го дава првиот знак", "The conductor gives the first cue"),
        ("GE-x38xCebQ", "Оперска вечер", "An evening at the opera"),
        ("8J3TaXShe-s", "Оркестар и диригент", "Orchestra and conductor"),
        ("J30aQDLRb_o", "Оркестар и хор во финалето", "Orchestra and choir in the finale"),
        ("35fycLrg6U8", "Клавирот подготвен за солистот", "The piano ready for the soloist"),
    ],
    "antique-theatre-views": [
        ("EduuFaEqPXY", "Античкиот театар во Стариот град на Охрид", "The Antique Theatre in Ohrid's Old Town"),
        ("qo47U7BCFW4", "Охрид и Самоиловата тврдина од воздух", "Ohrid and Samuel's Fortress from the air"),
        ("VSl-PTAtcnE", "Стариот град над езерото", "The Old Town above the lake"),
        ("sEdcXvbK4hU", "Зајдисонце над Охридското Езеро", "Sunset over Lake Ohrid"),
        ("7RFYSNvfCog", "Брегот на Охридското Езеро", "The shore of Lake Ohrid"),
        ("UQMql5WGno0", "Самоиловата тврдина и Охрид", "Samuel's Fortress and Ohrid"),
    ],
    "st-sophia-interiors": [
        ("K7KzgdURSSA", "Црквата „Св. Софија“, Охрид", "The Church of St. Sophia, Ohrid"),
        ("joiSqlLSklA", "Фрески во православна црква (илустрација)", "Frescoes in an Orthodox church (illustrative)"),
        ("5I8JElwzULE", "Ѕид со фрески (илустрација)", "A wall of frescoes (illustrative)"),
        ("ZaZcvU_WuF8", "Хор во црква", "A choir in a church"),
        ("KlgPfP3BJCU", "Харфа во концерт", "A harp in concert"),
        ("2R19bj6_enc", "Кандило и фрески (илустрација)", "Chandelier and frescoes (illustrative)"),
    ],
    "festival-atmosphere-2026": [
        ("vrAOnuqwIX8", "Кафеана во Охрид навечер", "A café in Ohrid at night"),
        ("PubTsM3Bx4c", "Светла во Стариот град", "Lights in the Old Town"),
        ("oa8oxsxcL6o", "Скали во Стариот град", "Steps in the Old Town"),
        ("o5vBHDJGJww", "Охридското крајбрежје навечер", "The Ohrid waterfront in the evening"),
        ("N6AZ_jjlLek", "Знамето над охридското пристаниште", "The flag above Ohrid's harbour"),
        ("9D45bVGo324", "Пристан на Охридското Езеро", "A pier on Lake Ohrid"),
        ("WUYC7hbia6Y", "Св. Јован Канео над езерото", "St. John at Kaneo above the lake"),
        ("vg-xzaHmjb4", "Зајдисонце на езерото", "Sunset on the lake"),
    ],
    "backstage-rehearsals": [
        ("dadr6UMaAoA", "Сцената пред монтажата на светлото", "The stage before the lighting rig goes up"),
        ("mn6DDkoBIss", "Рефлектори зад завесата", "Spotlights behind the curtain"),
        ("8XxF2kYHIgo", "Проба на светлото", "Lighting rehearsal"),
        ("jhPSJDhiRyQ", "Сценографија во сина светлина", "A stage set in blue light"),
        ("rPOmLGwai2w", "Нотите на пултот", "Sheet music on the stand"),
        ("slbOcNlWNHA", "Проба на гудачите", "String rehearsal"),
    ],
    "edition-2025-highlights": [
        ("zCZzSK5yX5A", "Балетска вечер", "A ballet evening"),
        ("dBWvUqBoOU8", "Џез вечер", "A jazz evening"),
        ("p6rNTdAPbuk", "Театарска претстава", "A theatre performance"),
        ("J30aQDLRb_o", "Оркестар и хор", "Orchestra and choir"),
        ("Ldb5VYH1FDc", "Св. Јован Канео", "St. John at Kaneo"),
        ("w23ynvKCXlk", "Кино на отворено", "An open-air cinema"),
    ],
}
