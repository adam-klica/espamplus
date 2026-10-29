// Generates the block theme's templates/*.html and parts/*.html from one place,
// so every section uses identical, valid block markup. Edit this file, not the .html output.
//   node tools/build-templates.mjs
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', 'theme', 'espam');

// ---------- block markup helpers ----------
const json = (o) => (o && Object.keys(o).length ? ' ' + JSON.stringify(o) : '');
const self = (name, attrs) => `<!-- wp:${name}${json(attrs)} /-->`;
const j = (...xs) => xs.filter((x) => x !== '').join('\n');
const group = (cls, inner, tag = 'div', extra = {}) =>
  `<!-- wp:group ${JSON.stringify({ ...(tag !== 'div' ? { tagName: tag } : {}), ...extra, className: cls, layout: { type: 'default' } })} -->\n` +
  `<${tag}${extra.anchor ? ` id="${extra.anchor}"` : ''} class="wp-block-group ${cls}">${inner ? '\n' + inner + '\n' : ''}</${tag}>\n<!-- /wp:group -->`;
const heading = (html, level = 2, cls = '') =>
  `<!-- wp:heading${json({ ...(level !== 2 ? { level } : {}), ...(cls ? { className: cls } : {}) })} -->\n` +
  `<h${level} class="wp-block-heading${cls ? ' ' + cls : ''}">${html}</h${level}>\n<!-- /wp:heading -->`;
const para = (html, cls = '') =>
  `<!-- wp:paragraph${json(cls ? { className: cls } : {})} -->\n<p${cls ? ` class="${cls}"` : ''}>${html}</p>\n<!-- /wp:paragraph -->`;
const list = (items, cls = '') =>
  `<!-- wp:list${json(cls ? { className: cls } : {})} -->\n<ul class="wp-block-list${cls ? ' ' + cls : ''}">` +
  items.map((i) => `<!-- wp:list-item -->\n<li>${i}</li>\n<!-- /wp:list-item -->`).join('') +
  `</ul>\n<!-- /wp:list -->`;
const button = (text, href, cls) =>
  `<!-- wp:button {"className":"${cls}"} -->\n<div class="wp-block-button ${cls}"><a class="wp-block-button__link wp-element-button" href="${href}">${text}</a></div>\n<!-- /wp:button -->`;
const buttons = (...bs) => `<!-- wp:buttons {"className":"ep-actions"} -->\n<div class="wp-block-buttons ep-actions">${bs.join('\n')}</div>\n<!-- /wp:buttons -->`;
const part = (slug, area) => self('template-part', { slug, area, tagName: area });
const page = (main) => j(part('header', 'header'), group('ep-main', main, 'main'), part('footer', 'footer'), '');

const wrap = (inner) => group('ep-wrap', inner);
const section = (cls, inner, anchor) => group(`ep-section ep-reveal ${cls}`.trim(), wrap(inner), 'section', anchor ? { anchor } : {});
const sectionHead = (title, text, cls = '') => group(`ep-section__head ${cls}`.trim(), j(heading(title, 2, 'ep-h2'), text ? para(text, 'ep-section__text') : ''));
const INQUIRY = '/kontakt/#upit';
const photo = (name, cls = '') => self('espam/photo', { name, ...(cls ? { className: cls } : {}) });

// Page title band: deep blue with a blueprint grid, the logo mark and up to three short facts (chips).
const pageHead = (title, lead, chips = []) =>
  group('ep-pagehead', wrap(group('ep-pagehead__in', j(self('espam/breadcrumbs'), heading(title, 1, 'ep-pagehead__title'), lead ? para(lead, 'ep-pagehead__lead') : '', chips.length ? list(chips, 'ep-pagehead__chips') : '', self('espam/logo', { variant: 'mark', className: 'ep-pagehead__mark' })))), 'section');

// Icon cards from a set in espam_feature_sets() (about, principles, offer, request-tips, b2b-modes).
const cards = (set) => self('espam/features', { set });

// Big-number band. Only numbers that are true by definition — no invented client counts or years.
const stat = (n, t) => group('ep-stat', j(para(n, 'ep-stat__n'), para(t, 'ep-stat__t')));
const stats = (cls = '') =>
  section(`ep-stats-section ${cls}`.trim(), group('ep-stats', j(
    stat('8', 'grupa proizvoda i roba po posebnom zahtjevu'),
    stat('4', 'koraka od upita do isporuke'),
    stat('1', 'kontakt osoba za sve kategorije'),
    stat('0', 'vezanosti za jedan brend ili proizvođača'),
  )));

// Statement band on deep blue.
const quote = (text, sub) =>
  section('ep-quote', group('ep-quote__in', j(self('espam/logo', { variant: 'mark', className: 'ep-quote__mark' }), para(text, 'ep-quote__text'), sub ? para(sub, 'ep-quote__sub') : '')));

// "Classic wholesale" vs ESPAM PLUS, side by side.
const compare = (title, text, them, us) =>
  section('', j(sectionHead(title, text), group('ep-compare', j(
    group('ep-compare__col ep-compare__col--them', j(heading('Klasična veleprodaja', 3, 'ep-compare__h'), list(them, 'ep-compare__list'))),
    group('ep-compare__col ep-compare__col--us', j(heading('ESPAM PLUS', 3, 'ep-compare__h'), list(us, 'ep-compare__list'))),
  ))));
const compareDefault = () => compare(
  'Drugačiji model veleprodaje',
  'Klasična veleprodaja prodaje ono što ima na lageru. Mi krećemo od vašeg spiska i tražimo robu za njega.',
  ['Nudi robu koju već ima na lageru', 'Asortiman vezan za nekoliko brendova', 'Za drugu kategoriju tražite drugog dobavljača', 'Cjenovnik koji brzo zastari'],
  ['Kreće od proizvoda koji vama treba', 'Bez ograničenja na jedan brend ili proizvođača', 'Jedan upit i jedan kontakt za sve kategorije', 'Aktuelna ponuda za svaki upit: cijena, dostupnost, rok'],
);

const cta = (title, text) =>
  section('ep-cta', group('ep-cta__box', j(heading(title, 2, 'ep-cta__title'), para(text, 'ep-cta__text'), buttons(button('Zatražite ponudu', INQUIRY, 'ep-btn ep-btn--light')))));

// ---------- shared content ----------
const stepsBlock = (detail = false) => self('espam/features', { set: detail ? 'steps-detail' : 'steps' });


const advantagesBlock = self('espam/features', { set: 'advantages' });

const segmentsBlock = self('espam/features', { set: 'segments' });

const faqSection = section('ep-faq-section', group('ep-faq-wrap', j(
  group('ep-faq-wrap__head', j(heading('Česta pitanja', 2, 'ep-h2'), para('Odgovori na pitanja koja kupci najčešće postavljaju prije prve saradnje. Ne vidite svoje pitanje? Pošaljite nam ga zajedno sa upitom.', 'ep-section__text'), buttons(button('Postavite pitanje', INQUIRY, 'ep-btn ep-btn--primary')))),
  self('espam/faq'),
)));

// ---------- parts ----------
const header = group('ep-header', group('ep-wrap ep-header__in', j(self('espam/logo'), self('espam/nav'))), 'div');

const footer = group(
  'ep-footer',
  j(
    group(
      'ep-wrap ep-footer__grid',
      j(
        group('ep-footer__brand', j(self('espam/logo', { color: 'light' }), para('Veleprodaja širokog asortimana robe. Vi tražite proizvod — mi pronalazimo dobavljača.', 'ep-footer__about'), self('espam/chat-links'))),
        group('ep-footer__col', j(heading('Firma', 2, 'ep-footer__h'), self('espam/company', { variant: 'footer' }))),
        group('ep-footer__col', j(heading('Sajt', 2, 'ep-footer__h'), self('espam/nav', { variant: 'footer' }))),
      ),
    ),
    group('ep-wrap ep-footer__bottom', j(para('© ESPAM PLUS DOO, Berane. Sva prava zadržana.'), para('Cijene i dostupnost dajemo na upit.'))),
  ),
  'footer',
);

// ---------- front page ----------
const front = page(
  j(
    group(
      'ep-hero',
      j(
        group(
          'ep-wrap ep-hero__in',
          j(
            group(
              'ep-hero__text',
              j(
                para('Veleprodaja iz Berana, Crna Gora', 'ep-hero__where'),
                heading('Vi tražite proizvod. <span>Mi pronalazimo dobavljača.</span>', 1, 'ep-hero__title'),
                para('Veleprodaja širokog asortimana robe za firme, prodavnice i ugostitelje. Robu nabavljamo prema vašim potrebama — od jednog artikla do cijele liste.', 'ep-hero__lead'),
                buttons(button('Zatražite ponudu', INQUIRY, 'ep-btn ep-btn--primary'), button('Pogledajte asortiman', '/asortiman/', 'ep-btn ep-btn--ghost')),
              ),
            ),
            group('ep-hero__media', j(self('espam/network'), para('<strong>Jedan upit</strong> — mi ga šaljemo pravim dobavljačima i vraćamo vam najbolju ponudu.', 'ep-hero__caption'))),
          ),
        ),
        group('ep-wrap ep-values-wrap', self('espam/features', { set: 'values' })),
      ),
      'section',
    ),
    self('espam/marquee'),
    section('ep-intro-section', group('ep-intro', j(
      group('ep-intro__media', j(photo('hero', 'ep-intro__photo'), group('ep-intro__badge', j(self('espam/hero-mark'), para('<strong>Jedan upit.</strong> Više dobavljača. Najbolja ponuda.', 'ep-intro__badge-text'))))),
      group('ep-intro__body', j(
        heading('Veleprodaja koja počinje vašim zahtjevom', 2, 'ep-h2'),
        para('ESPAM PLUS DOO je veleprodajna firma iz Berana. Ne nudimo unaprijed zadat katalog — polazimo od onoga što vam je potrebno. Bilo da opremate kancelariju, dopunjujete asortiman prodavnice ili opremate ugostiteljski objekat, mi pronalazimo dobavljača, upoređujemo ponude i organizujemo isporuku.', 'ep-section__text'),
        para('Tako dobijate širok izbor bez ograničenja na jedan brend, jednu osobu za kontakt i ponudu koja odgovara vašem budžetu i rokovima.', 'ep-section__text'),
        buttons(button('Više o nama', '/o-nama/', 'ep-btn ep-btn--ghost')),
      )),
    ))),
    section('ep-cats-section', j(sectionHead('Asortiman bez ograničenja na jedan brend', 'Ne držimo jednu paletu proizvoda. Za svaku kategoriju nalazimo dobavljača koji ima ono što vam treba — po cijeni i roku koji vam odgovaraju.'), self('espam/categories'))),
    section('ep-steps-section', j(sectionHead('Kako funkcioniše saradnja', 'Četiri koraka od upita do isporuke.'), stepsBlock(), buttons(button('Detaljnije o saradnji', '/saradnja/', 'ep-btn ep-btn--ghost')))),
    section('ep-tint', group('ep-feature', j(photo('logistics', 'ep-feature__photo'), group('ep-feature__body', j(heading('Nemamo svoju paletu. Zato možemo nabaviti gotovo sve.', 2, 'ep-h2'), advantagesBlock))))),
    section('ep-blue', j(group('ep-b2b', j(group('ep-b2b__text', j(heading('Pouzdan partner za veleprodajnu nabavku', 2, 'ep-h2'), para('Poslovnim kupcima omogućavamo pristup širokom spektru proizvoda kroz fleksibilnu nabavku od domaćih i međunarodnih dobavljača.', 'ep-section__text'), para('Prodavnice, marketi, ugostitelji i firme dobijaju jednog partnera za nabavku iz svih kategorija — redovno ili kada zatreba.', 'ep-section__text'), buttons(button('Saradnja za firme', '/b2b/', 'ep-btn ep-btn--light')))), photo('b2b', 'ep-b2b__photo'))))),
    cta('Imate spisak proizvoda?', 'Pošaljite ga — vraćamo ponudu sa cijenom, dostupnošću i rokom isporuke.'),
  ),
);

// ---------- pages ----------
const about = page(
  j(
    pageHead('O nama', 'ESPAM PLUS DOO je veleprodajna firma iz Berana. Kupcima nabavljamo robu iz osam grupa proizvoda — i sve što nije na listi.', ['Sjedište u Beranama', 'Domaći i međunarodni dobavljači', 'B2B kupci širom Crne Gore']),
    section('', group('ep-feature', j(photo('berane-mapa', 'ep-feature__photo'), group('ep-feature__body', j(
      para('Naša priča', 'ep-eyebrow'),
      heading('Firma iz Berana, nabavka bez granica', 2, 'ep-h2'),
      para('ESPAM PLUS DOO ima sjedište u Beranama, u ulici VIII crnogorske brigade broj 5. Odavde organizujemo nabavku i isporuku za kupce iz cijele Crne Gore.', 'ep-section__text'),
      para('Firmu smo zamislili oko jednog jednostavnog pitanja: šta je kupcu zaista potrebno? Zato ne počinjemo od kataloga, nego od vašeg spiska — i tek onda tražimo dobavljača.', 'ep-section__text'),
      list(['<strong>Sjedište</strong> VIII crnogorske brigade 5, Berane', '<strong>Tržište</strong> Crna Gora', '<strong>Kupci</strong> prodavnice, marketi, ugostitelji i firme'], 'ep-meta'),
    ))))),
    stats(),
    section('', j(sectionHead('Ukratko o nama', 'Tri stvari koje treba da znate prije prvog upita.'), cards('about'))),
    quote('Vi tražite proizvod. <span>Mi pronalazimo dobavljača.</span>', 'Nemamo svoju paletu proizvoda — i upravo zato možemo nabaviti ono što vam treba, a ne ono što nam je ostalo na lageru.'),
    section('', group('ep-longtext', j(
      para('Kako radimo', 'ep-eyebrow'),
      heading('Naš pristup', 2, 'ep-h2'),
      group('ep-longtext__cols', j(
        para('Većina veleprodaja nudi ono što ima na lageru. Mi radimo obrnuto: prvo saslušamo šta vam treba, a onda tražimo dobavljača koji to može isporučiti po najboljim uslovima. Zato pitanje rijetko glasi „da li imate", nego „kada i po kojoj cijeni".'),
        para('Za svaki upit kontaktiramo više dobavljača, upoređujemo cijene i rokove i šaljemo vam ponudu sa jasnim uslovima. Kada je prihvatite, preuzimamo komunikaciju, nabavku i organizaciju isporuke — vi imate jednog sagovornika od prvog upita do isporuke.'),
      )),
    ))),
    compareDefault(),
    section('ep-tint', j(sectionHead('Na čemu gradimo saradnju', 'Principi kojih se držimo u svakom upitu, bez obzira na to da li je u pitanju jedan artikal ili cijela lista.'), cards('principles'))),
    cta('Recite nam šta vam treba', 'Pošaljite upit, a mi se javljamo sa ponudom — cijenom, dostupnošću i rokom isporuke.'),
  ),
);

const assortment = page(
  j(
    pageHead('Asortiman', 'Ne prikazujemo cjenovnik od 500 proizvoda koji sutra možda neće biti dostupni. Pošaljite upit — dobijate aktuelnu ponudu i dostupnost.', ['8 grupa proizvoda', 'Roba i van liste', 'Ponuda za svaki upit']),
    section('ep-tint ep-section--tight', group('ep-howto', j(
      group('ep-howto__head', j(para('Prije upita', 'ep-eyebrow'), heading('Tri podatka za brzu ponudu', 2, 'ep-h2'))),
      cards('request-tips'),
    ))),
    section('', j(sectionHead('Grupe proizvoda', 'Izaberite kategoriju i pošaljite upit — kategorija se automatski upisuje u formu.'), self('espam/categories', { variant: 'full' }))),
    section('', group('ep-feature', j(photo('boxes-shelves', 'ep-feature__photo'), group('ep-feature__body', j(
      para('Zašto bez cjenovnika', 'ep-eyebrow'),
      heading('Aktuelna cijena umjesto zastarjelog kataloga', 2, 'ep-h2'),
      para('Cijene i dostupnost robe kod dobavljača mijenjaju se iz sedmice u sedmicu. Umjesto kataloga koji kasni, za svaki upit provjeravamo stanje i šaljemo vam ponudu koja važi danas.', 'ep-section__text'),
      list(['Cijena za vašu količinu, ne kataloška', 'Provjerena dostupnost kod dobavljača', 'Prijedlog zamjene ako model nije dostupan'], 'ep-checklist'),
    ))))),
    cta('Imate spisak proizvoda?', 'Pošaljite ga u jednom upitu, za sve kategorije odjednom — vraćamo jednu ponudu.'),
  ),
);

const cooperation = page(
  j(
    pageHead('Kako funkcioniše saradnja', 'Od upita do isporuke u četiri jasna koraka — vi pošaljete spisak, mi radimo ostalo.', ['Upit za par minuta', 'Ponuda na vaš email', 'Isporuka na vašu adresu']),
    section('', j(sectionHead('Četiri koraka', 'Isti proces važi za jedan artikal i za cijelu listu.'), stepsBlock(true))),
    section('ep-tint', j(sectionHead('Šta dobijate u ponudi', 'Ponuda je jasna i potpuna, da biste mogli odmah da odlučite.'), cards('offer'))),
    section('', group('ep-feature', j(photo('delivery-truck', 'ep-feature__photo'), group('ep-feature__body', j(
      para('Poslije prihvatanja ponude', 'ep-eyebrow'),
      heading('Nabavka i isporuka bez brige', 2, 'ep-h2'),
      para('Kada prihvatite ponudu, mi preuzimamo komunikaciju sa dobavljačem, organizujemo nabavku i dogovaramo isporuku na vašu adresu. O svakoj promjeni roka javljamo vam se na vrijeme.', 'ep-section__text'),
      list(['Jedan sagovornik do kraja porudžbine', 'Organizovan transport do vas', 'Rok isporuke naveden u ponudi'], 'ep-checklist'),
    ))))),
    section('ep-tint', group('ep-split', j(
      group('ep-split__col', j(para('Savjet', 'ep-eyebrow'), heading('Šta da navedete u upitu', 2, 'ep-h2'), para('Što je upit precizniji, to je ponuda brža i tačnija. Ako ne znate tačan model, opišite namjenu.'))),
      group('ep-split__col ep-split__col--wide', list([
        'Naziv ili opis proizvoda (model, specifikacija ili namjena)',
        'Količinu koja vam je potrebna',
        'Željeni rok isporuke',
        'Mjesto isporuke',
        'Naziv firme i kontakt osobu, ako ste poslovni kupac',
      ], 'ep-checklist')),
    ))),
    faqSection,
    cta('Spremni ste za prvi upit?', 'Forma je kratka — a ponudu šaljemo na vaš email.'),
  ),
);

const b2b = page(
  j(
    pageHead('Za poslovne kupce', 'Pouzdan partner za veleprodajnu nabavku: širok spektar proizvoda, fleksibilna nabavka i jedan kontakt.', ['Prodavnice i marketi', 'Ugostitelji', 'Firme i kancelarije']),
    section('', j(sectionHead('Kome smo partner', 'Saradnja je prilagođena kupcima koji redovno ili povremeno nabavljaju robu u većim količinama.'), segmentsBlock)),
    section('', j(sectionHead('Dva načina saradnje', 'Izaberite ono što odgovara vašem poslovanju — ili kombinujte oba.'), cards('b2b-modes'))),
    section('ep-tint', group('ep-longtext', j(
      para('Za firme', 'ep-eyebrow'),
      heading('Nabavka bez vezivanja za jednog proizvođača', 2, 'ep-h2'),
      group('ep-longtext__cols', j(
        para('Poslovnim kupcima omogućavamo pristup širokom spektru proizvoda kroz fleksibilnu nabavku od domaćih i međunarodnih dobavljača. Ne morate tražiti više dobavljača za različite kategorije — dovoljno je da nam pošaljete listu.'),
        para('Za redovne nabavke dogovaramo dinamiku i količine prema vašim potrebama, a za jednokratne narudžbe pripremamo ponudu za svaki upit posebno. U oba slučaja dobijate jasnu ponudu sa cijenom, dostupnošću i rokom isporuke.'),
      )),
    ))),
    section('', group('ep-feature', j(photo('courier', 'ep-feature__photo'), group('ep-feature__body', j(
      para('Za poslovne kupce', 'ep-eyebrow'),
      heading('Šta dobijate kao poslovni kupac', 2, 'ep-h2'),
      para('Nabavka za firmu ne treba da znači deset dobavljača, deset faktura i deset rokova. Kod nas sve ide kroz jedan upit i jednu ponudu.', 'ep-section__text'),
      list([
        'Jednu ponudu za robu iz više kategorija',
        'Ponudu prilagođenu vašim količinama',
        'Dogovorenu dinamiku za redovne nabavke',
        'Organizovanu isporuku na adresu objekta',
        'Istu kontakt osobu za svaku porudžbinu',
      ], 'ep-checklist'),
    ))))),
    cta('Pošaljite listu za ponudu', 'Navedite proizvode i količine — odgovaramo sa cijenom, dostupnošću i rokom isporuke.'),
  ),
);

const contact = page(
  j(
    pageHead('Kontakt', 'Pošaljite upit preko forme ili nas kontaktirajte direktno — telefonom, emailom, Viberom ili WhatsAppom.', ['Odgovor sa ponudom', 'Potvrda upita na email', 'Berane, Crna Gora']),
    group('ep-channels-wrap', wrap(self('espam/contact-channels')), 'section'),
    section('ep-contact-section', group('ep-contact', j(
      group('ep-contact__aside', j(
        para('Nakon slanja', 'ep-eyebrow ep-eyebrow--light'),
        heading('Šta se dešava sa vašim upitom', 2, 'ep-contact__aside-title'),
        list([
          '<strong>Odmah</strong>Na vaš email stiže potvrda da je zahtjev za ponudu primljen.',
          '<strong>Provjeravamo</strong>Kontaktiramo dobavljače i upoređujemo cijene, dostupnost i rokove.',
          '<strong>Šaljemo ponudu</strong>Dobijate cijenu, dostupnost i rok isporuke — na email ili telefonom.',
        ], 'ep-after'),
        group('ep-contact__company', self('espam/company')),
      )),
      group('ep-contact__form', j(
        group('ep-contact__form-head', j(para('Upit za ponudu', 'ep-eyebrow'), heading('Zatražite ponudu', 2, 'ep-h2'), para('Navedite proizvode i količine — što preciznije, to brže. Polja sa * su obavezna.', 'ep-section__text'))),
        self('espam/form'),
      ), 'div', { anchor: 'upit' }),
    ))),
    group('ep-map-section', self('espam/map'), 'section'),
  ),
);

// Generic page (e.g. Politika privatnosti): title from the page, content from the editor.
const pageTpl = page(
  j(
    group('ep-pagehead', wrap(group('ep-pagehead__in', j(self('espam/breadcrumbs'), self('post-title', { level: 1, className: 'ep-pagehead__title' }), self('espam/logo', { variant: 'mark', className: 'ep-pagehead__mark' })))), 'section'),
    section('', group('ep-prose', self('post-content', { layout: { type: 'default' } }))),
  ),
);

const privacy = page(
  j(
    group('ep-pagehead', wrap(group('ep-pagehead__in', j(self('espam/breadcrumbs'), self('post-title', { level: 1, className: 'ep-pagehead__title' }), self('espam/logo', { variant: 'mark', className: 'ep-pagehead__mark' })))), 'section'),
    section('', j(group('ep-prose', self('post-content', { layout: { type: 'default' } })), group('ep-prose', j(heading('Fotografije na sajtu', 2), self('espam/photo-credits')), 'div', { anchor: 'fotografije' }))),
  ),
);

const notFound = page(
  j(
    group('ep-404', wrap(j(para('404', 'ep-404__code'), heading('Ova stranica ne postoji.', 1, 'ep-404__title'), para('Možda je link zastario. Pogledajte asortiman ili nam pošaljite upit.', 'ep-section__text'), buttons(button('Asortiman', '/asortiman/', 'ep-btn ep-btn--primary'), button('Početna', '/', 'ep-btn ep-btn--ghost')))), 'section'),
  ),
);

// Fallback for anything else (blog/archive if ever enabled).
const index = page(
  section('', j(
    `<!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":true}} -->\n<div class="wp-block-query">` +
      `<!-- wp:post-template -->\n${group('ep-post', j(self('post-title', { isLink: true, level: 2 }), self('post-date'), self('post-excerpt')))}\n<!-- /wp:post-template -->` +
      `\n<!-- wp:query-pagination -->\n${self('query-pagination-previous')}\n${self('query-pagination-numbers')}\n${self('query-pagination-next')}\n<!-- /wp:query-pagination --></div>\n<!-- /wp:query -->`,
  )),
);

const out = {
  'parts/header.html': header + '\n',
  'parts/footer.html': footer + '\n',
  'templates/front-page.html': front,
  'templates/page-o-nama.html': about,
  'templates/page-asortiman.html': assortment,
  'templates/page-saradnja.html': cooperation,
  'templates/page-b2b.html': b2b,
  'templates/page-kontakt.html': contact,
  'templates/page.html': pageTpl,
  'templates/page-politika-privatnosti.html': privacy,
  'templates/404.html': notFound,
  'templates/index.html': index,
};
for (const [file, content] of Object.entries(out)) {
  fs.mkdirSync(path.dirname(path.join(root, file)), { recursive: true });
  fs.writeFileSync(path.join(root, file), content);
}
console.log('written:', Object.keys(out).join(', '));
