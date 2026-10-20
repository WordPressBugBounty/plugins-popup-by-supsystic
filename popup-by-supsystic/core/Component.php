<?php

if (!defined('ABSPATH')) {
  exit();
}

class ComponentPpsgen
{
  public $namePrefix = '';
  public $css = '';
  protected FramePpsgen $_frame;
  protected array $requestedGoogleFonts = [];

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_frame = $frame;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function getCss()
  {
    return $this->css;
  }

  public function setCss($css)
  {
    $this->css .= $css . PHP_EOL;
  }

  public function cleanCss()
  {
    $this->css = '';
    $this->requestedGoogleFonts = [];
  }

  public function setNamePrefix($prefix = '')
  {
    $this->namePrefix = esc_attr($prefix);
  }

  public function nameToClassId($name, $params = [])
  {
    if (!empty($params['attrs']) && strpos($params['attrs'], 'id="') !== false) {
      preg_match('/id="(.+)"/ui', $params['attrs'], $idMatches);
      if (!empty($idMatches[1])) {
        return $idMatches[1];
      }
    }
    return str_replace(['[', ']'], '', $name);
  }

  public function echoEscapedHtml($html)
  {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->getFrame()->core('helper')->wpKses($html);
  }

  public function skipHtmlEscape($safe_text, $text)
  {
    return $text;
  }

  public function input($name, $params = ['attrs' => '', 'type' => 'text', 'value' => ''])
  {
    $defaults = ['attrs' => '', 'type' => 'text', 'value' => ''];
    $params = wp_parse_args($params, $defaults);
    $params['attrs'] .= $this->_dataToAttrs($params);

    if (!empty($params['required'])) {
      $params['attrs'] .= ' required ';
    }
    if (!empty($params['placeholder'])) {
      $params['attrs'] .= ' placeholder="' . esc_attr($params['placeholder']) . '"';
    }
    if (!empty($params['disabled'])) {
      $params['attrs'] .= ' disabled ';
    }
    if (!empty($params['readonly'])) {
      $params['attrs'] .= ' readonly ';
    }

    echo '<input type="' . esc_attr($params['type']) . '" name="' . (!empty(esc_attr($name)) ? esc_attr($this->namePrefix) . esc_attr($name) : '') . '" value="' . esc_attr($params['value']) . '" ';
    if (!empty($params['attrs'])) {
      $this->echoEscapedHtml($params['attrs']);
    }
    echo ' />';
  }

  private function _dataToAttrs($params)
  {
    $res = '';
    foreach ($params as $k => $v) {
      if (strpos($k, 'data-') === 0) {
        $res .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
      }
    }
    return $res;
  }

  public function text($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'text';
    $this->input($name, $params);
  }

  public function number($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'number';
    $this->input($name, $params);
  }

  public function email($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'email';
    $this->input($name, $params);
  }

  public function phone($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'tel';
    $this->input($name, $params);
  }

  public function date($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'date';
    $this->input($name, $params);
  }

  public function password($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'password';
    $this->input($name, $params);
  }

  public function datetime($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'datetime-local';
    $this->input($name, $params);
  }

  public function month($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'month';
    $this->input($name, $params);
  }

  public function week($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'week';
    $this->input($name, $params);
  }

  public function time($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'time';
    $this->input($name, $params);
  }

  public function url($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'url';
    $this->input($name, $params);
  }

  public function range($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'range';
    $this->input($name, $params);
  }

  public function color($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'color';
    $this->input($name, $params);
  }

  public function hidden($name, $params = ['attrs' => '', 'value' => ''])
  {
    $params['type'] = 'hidden';
    $this->input($name, $params);
  }

  public function checkbox($name, $params = ['attrs' => '', 'value' => '', 'checked' => false])
  {
    $params['type'] = 'checkbox';
    $params['checked'] = !empty($params['checked']) ? 'checked' : '';
    if (empty($params['value'])) {
      $params['value'] = 1;
    }
    $this->input($name, ['type' => 'hidden', 'value' => 0]);
    $this->input($name, $params);
  }

  public function checkboxToggle($name, $params = ['attrs' => '', 'class' => '', 'label' => '', 'value' => '', 'checked' => ''])
  {
    $params['class'] = !empty($params['class']) ? $params['class'] : '';
    $params['type'] = 'checkbox';
    $params['checked'] = isset($params['checked']) && $params['checked'] ? 'checked' : '';
    if (empty($params['value'])) {
      $params['value'] = 1;
    }
    $id = empty($params['id']) ? $this->nameToClassId($name) . wp_rand(9, 9999) : $params['id'];
    $params['attrs'] = 'id="' . esc_attr($id) . '" class="' . esc_attr($params['class']) . '" ' . $params['checked'];
    echo '<div class="ppsgenSwitchWrapper">';
    echo '<div class="ppsgenSwitch">';
    $this->input($name, ['type' => 'hidden', 'value' => 0]);
    $this->input($name, $params);
    echo '<label for="' . esc_attr($id) . '" class=""></label>';
    echo '</div>';
    echo '<label for="' . esc_attr($id) . '" class="">';
    echo esc_html($params['label'] ?? '');
    echo '</label>';
    echo '</div>';
  }

  public function select($name, $params = ['options' => [], 'attrs' => '', 'class' => '', 'selected' => ''])
  {
    $attrs = isset($params['attrs']) ? esc_attr($params['attrs']) : '';
    $class = isset($params['class']) ? esc_attr($params['class']) : '';
    $selected = isset($params['selected']) ? $params['selected'] : '';
    echo '<select name="' . esc_attr($this->namePrefix) . esc_attr($name) . '" ' . ' class="' . esc_attr($class) . '" ' . esc_attr($attrs) . '>';

    foreach ($params['options'] as $value => $label) {
      $isSelected = selected($selected, $value, false);
      echo '<option value="' . esc_attr($value) . '"' . esc_attr($isSelected) . '>' . esc_html($label) . '</option>';
    }

    echo '</select>';
  }

  public function selectmultiple($name, $params = ['options' => [], 'attrs' => '', 'class' => '', 'selected' => ''])
  {
    $attrs = isset($params['attrs']) ? esc_attr($params['attrs']) : '';
    $class = isset($params['class']) ? esc_attr($params['class']) : '';
    $selectedList = isset($params['selected']) ? $params['selected'] : [];
    echo '<select multiple="multiple"  name="' . esc_attr($this->namePrefix) . esc_attr($name) . '" ' . ' class="' . esc_attr($class) . '" ' . esc_attr($attrs) . '>';
    if (!empty($params['options'])) {
      foreach ($params['options'] as $value => $label) {
        $selected = in_array($value, (array) $selectedList) ? ' selected="true"' : '';
        echo '<option value="' . esc_attr($value) . '"' . esc_attr($selected) . '>' . esc_html($label) . '</option>';
      }
    }
    echo '</select>';
  }

  public function fontFamilySelect($name, $params = ['attrs' => '', 'class' => '', 'selected' => ''])
  {
    $attrs = isset($params['attrs']) ? $params['attrs'] : '';
    $class = trim((isset($params['class']) ? $params['class'] : '') . ' ppsgenFontFamilySelect');
    $selected = isset($params['selected']) ? (string) $params['selected'] : '';
    $selectName = esc_attr($this->namePrefix) . esc_attr($name);
    $systemFonts = $this->getEmailFonts(true);
    $googleFonts = $this->getGoogleFontsList();

    echo '<span class="ppsgenFontFamilyPicker">';
    echo '<select name="' . esc_attr($selectName) . '" class="' . esc_attr($class) . '" ' . esc_attr($attrs) . '>';
    echo '<optgroup label="' . esc_attr__('Default Fonts', 'ppsgen') . '">';
    foreach ($systemFonts as $value => $label) {
      $style = $this->getSystemFontInlineCss((string) $value);
      $isSelected = selected($selected, (string) $value, false);
      echo '<option value="' . esc_attr($value) . '"' . esc_attr($isSelected) . ' style="' . esc_attr($style) . '">' . esc_html($label) . '</option>';
    }
    echo '</optgroup>';
    echo '<optgroup label="' . esc_attr__('Google Fonts', 'ppsgen') . '">';
    foreach ($googleFonts as $font) {
      $font = (string) $font;
      $value = 'google:' . $font;
      $isSelected = selected($selected, $value, false);
      echo '<option value="' . esc_attr($value) . '"' . esc_attr($isSelected) . ' style="' . esc_attr('font-family: "' . $font . '", sans-serif;') . '">' . esc_html($font) . '</option>';
    }
    echo '</optgroup>';
    echo '</select>';
    echo '<span class="ppsgenFontFamilyPreview" aria-hidden="true">' . esc_html__('Font Preview:', 'ppsgen') . ' <span class="ppsgenFontFamilyPreviewText">Aa</span></span>';
    echo '</span>';
  }

  public function getGoogleFontsList(): array
  {
    static $fonts = null;
    if ($fonts !== null) {
      return $fonts;
    }

    $fonts = [
      'Abel',
      'Abril Fatface',
      'Aclonica',
      'Acme',
      'Actor',
      'Adamina',
      'Advent Pro',
      'Aguafina Script',
      'Aladin',
      'Aldrich',
      'Alegreya',
      'Alegreya SC',
      'Alex Brush',
      'Alfa Slab One',
      'Alice',
      'Alike',
      'Alike Angular',
      'Allan',
      'Allerta',
      'Allerta Stencil',
      'Allura',
      'Almendra',
      'Almendra SC',
      'Amaranth',
      'Amatic SC',
      'Amethysta',
      'Andada',
      'Andika',
      'Angkor',
      'Annie Use Your Telescope',
      'Anonymous Pro',
      'Antic',
      'Antic Didone',
      'Antic Slab',
      'Anton',
      'Arapey',
      'Arbutus',
      'Architects Daughter',
      'Arimo',
      'Arizonia',
      'Armata',
      'Artifika',
      'Arvo',
      'Asap',
      'Asset',
      'Astloch',
      'Asul',
      'Atomic Age',
      'Aubrey',
      'Audiowide',
      'Average',
      'Averia Gruesa Libre',
      'Averia Libre',
      'Averia Sans Libre',
      'Averia Serif Libre',
      'Bad Script',
      'Balthazar',
      'Bangers',
      'Basic',
      'Battambang',
      'Baumans',
      'Bayon',
      'Belgrano',
      'Belleza',
      'Bentham',
      'Berkshire Swash',
      'Bevan',
      'Bigshot One',
      'Bilbo',
      'Bilbo Swash Caps',
      'Bitter',
      'Black Ops One',
      'Bokor',
      'Bonbon',
      'Boogaloo',
      'Bowlby One',
      'Bowlby One SC',
      'Brawler',
      'Bree Serif',
      'Bubblegum Sans',
      'Buda',
      'Buenard',
      'Butcherman',
      'Butterfly Kids',
      'Cabin',
      'Cabin Condensed',
      'Cabin Sketch',
      'Caesar Dressing',
      'Cagliostro',
      'Calligraffitti',
      'Cambo',
      'Candal',
      'Cantarell',
      'Cantata One',
      'Cardo',
      'Carme',
      'Carter One',
      'Caudex',
      'Cedarville Cursive',
      'Ceviche One',
      'Changa One',
      'Chango',
      'Chau Philomene One',
      'Chelsea Market',
      'Chenla',
      'Cherry Cream Soda',
      'Chewy',
      'Chicle',
      'Chivo',
      'Coda',
      'Coda Caption',
      'Codystar',
      'Comfortaa',
      'Coming Soon',
      'Concert One',
      'Condiment',
      'Content',
      'Contrail One',
      'Convergence',
      'Cookie',
      'Copse',
      'Corben',
      'Cousine',
      'Coustard',
      'Covered By Your Grace',
      'Crafty Girls',
      'Creepster',
      'Crete Round',
      'Crimson Text',
      'Crushed',
      'Cuprum',
      'Cutive',
      'Damion',
      'Dancing Script',
      'Dangrek',
      'Dawning of a New Day',
      'Days One',
      'Delius',
      'Delius Swash Caps',
      'Delius Unicase',
      'Della Respira',
      'Devonshire',
      'Didact Gothic',
      'Diplomata',
      'Diplomata SC',
      'Doppio One',
      'Dorsa',
      'Dosis',
      'Dr Sugiyama',
      'Droid Sans',
      'Droid Sans Mono',
      'Droid Serif',
      'Duru Sans',
      'Dynalight',
      'EB Garamond',
      'Eater',
      'Economica',
      'Electrolize',
      'Emblema One',
      'Emilys Candy',
      'Engagement',
      'Enriqueta',
      'Erica One',
      'Esteban',
      'Euphoria Script',
      'Ewert',
      'Exo',
      'Expletus Sans',
      'Fanwood Text',
      'Fascinate',
      'Fascinate Inline',
      'Federant',
      'Federo',
      'Felipa',
      'Fjord One',
      'Flamenco',
      'Flavors',
      'Fondamento',
      'Fontdiner Swanky',
      'Forum',
      'Francois One',
      'Fredericka the Great',
      'Fredoka One',
      'Freehand',
      'Fresca',
      'Frijole',
      'Fugaz One',
      'GFS Didot',
      'GFS Neohellenic',
      'Galdeano',
      'Gentium Basic',
      'Gentium Book Basic',
      'Geo',
      'Geostar',
      'Geostar Fill',
      'Germania One',
      'Give You Glory',
      'Glass Antiqua',
      'Glegoo',
      'Gloria Hallelujah',
      'Goblin One',
      'Gochi Hand',
      'Gorditas',
      'Goudy Bookletter 1911',
      'Graduate',
      'Gravitas One',
      'Great Vibes',
      'Gruppo',
      'Gudea',
      'Habibi',
      'Hammersmith One',
      'Handlee',
      'Hanuman',
      'Happy Monkey',
      'Henny Penny',
      'Herr Von Muellerhoff',
      'Holtwood One SC',
      'Homemade Apple',
      'Homenaje',
      'IM Fell DW Pica',
      'IM Fell DW Pica SC',
      'IM Fell Double Pica',
      'IM Fell Double Pica SC',
      'IM Fell English',
      'IM Fell English SC',
      'IM Fell French Canon',
      'IM Fell French Canon SC',
      'IM Fell Great Primer',
      'IM Fell Great Primer SC',
      'Iceberg',
      'Iceland',
      'Imprima',
      'Inconsolata',
      'Inder',
      'Indie Flower',
      'Inika',
      'Irish Grover',
      'Istok Web',
      'Italiana',
      'Italianno',
      'Jim Nightshade',
      'Jockey One',
      'Jolly Lodger',
      'Josefin Sans',
      'Josefin Slab',
      'Judson',
      'Julee',
      'Junge',
      'Jura',
      'Just Another Hand',
      'Just Me Again Down Here',
      'Kameron',
      'Karla',
      'Kaushan Script',
      'Kelly Slab',
      'Kenia',
      'Khmer',
      'Knewave',
      'Kotta One',
      'Koulen',
      'Kranky',
      'Kreon',
      'Kristi',
      'Krona One',
      'La Belle Aurore',
      'Lancelot',
      'Lato',
      'League Script',
      'Leckerli One',
      'Ledger',
      'Lekton',
      'Lemon',
      'Lilita One',
      'Limelight',
      'Linden Hill',
      'Lobster',
      'Lobster Two',
      'Londrina Outline',
      'Londrina Shadow',
      'Londrina Sketch',
      'Londrina Solid',
      'Lora',
      'Love Ya Like A Sister',
      'Loved by the King',
      'Lovers Quarrel',
      'Luckiest Guy',
      'Lusitana',
      'Lustria',
      'Macondo',
      'Macondo Swash Caps',
      'Magra',
      'Maiden Orange',
      'Mako',
      'Marck Script',
      'Marko One',
      'Marmelad',
      'Marvel',
      'Mate',
      'Mate SC',
      'Maven Pro',
      'Meddon',
      'MedievalSharp',
      'Medula One',
      'Merriweather',
      'Metal',
      'Metamorphous',
      'Michroma',
      'Miltonian',
      'Miltonian Tattoo',
      'Miniver',
      'Miss Fajardose',
      'Modern Antiqua',
      'Molengo',
      'Monofett',
      'Monoton',
      'Monsieur La Doulaise',
      'Montaga',
      'Montez',
      'Montserrat',
      'Moul',
      'Moulpali',
      'Mountains of Christmas',
      'Mr Bedfort',
      'Mr Dafoe',
      'Mr De Haviland',
      'Mrs Saint Delafield',
      'Mrs Sheppards',
      'Muli',
      'Mystery Quest',
      'Neucha',
      'Neuton',
      'News Cycle',
      'Niconne',
      'Nixie One',
      'Nobile',
      'Nokora',
      'Norican',
      'Nosifer',
      'Nothing You Could Do',
      'Noticia Text',
      'Nova Cut',
      'Nova Flat',
      'Nova Mono',
      'Nova Oval',
      'Nova Round',
      'Nova Script',
      'Nova Slim',
      'Nova Square',
      'Numans',
      'Nunito',
      'Odor Mean Chey',
      'Old Standard TT',
      'Oldenburg',
      'Oleo Script',
      'Open Sans',
      'Open Sans Condensed',
      'Orbitron',
      'Original Surfer',
      'Oswald',
      'Over the Rainbow',
      'Overlock',
      'Overlock SC',
      'Ovo',
      'Oxygen',
      'PT Mono',
      'PT Sans',
      'PT Sans Caption',
      'PT Sans Narrow',
      'PT Serif',
      'PT Serif Caption',
      'Pacifico',
      'Parisienne',
      'Passero One',
      'Passion One',
      'Patrick Hand',
      'Patua One',
      'Paytone One',
      'Permanent Marker',
      'Petrona',
      'Philosopher',
      'Piedra',
      'Pinyon Script',
      'Plaster',
      'Play',
      'Playball',
      'Playfair Display',
      'Podkova',
      'Poiret One',
      'Poller One',
      'Poly',
      'Pompiere',
      'Pontano Sans',
      'Port Lligat Sans',
      'Port Lligat Slab',
      'Prata',
      'Preahvihear',
      'Press Start 2P',
      'Princess Sofia',
      'Prociono',
      'Prosto One',
      'Puritan',
      'Quantico',
      'Quattrocento',
      'Quattrocento Sans',
      'Questrial',
      'Quicksand',
      'Qwigley',
      'Radley',
      'Raleway',
      'Rammetto One',
      'Rancho',
      'Rationale',
      'Redressed',
      'Reenie Beanie',
      'Revalia',
      'Ribeye',
      'Ribeye Marrow',
      'Righteous',
      'Rochester',
      'Rock Salt',
      'Rokkitt',
      'Ropa Sans',
      'Rosario',
      'Rosarivo',
      'Rouge Script',
      'Ruda',
      'Ruge Boogie',
      'Ruluko',
      'Ruslan Display',
      'Russo One',
      'Ruthie',
      'Sail',
      'Salsa',
      'Sancreek',
      'Sansita One',
      'Sarina',
      'Satisfy',
      'Schoolbell',
      'Seaweed Script',
      'Sevillana',
      'Shadows Into Light',
      'Shadows Into Light Two',
      'Shanti',
      'Share',
      'Shojumaru',
      'Short Stack',
      'Siemreap',
      'Sigmar One',
      'Signika',
      'Signika Negative',
      'Simonetta',
      'Sirin Stencil',
      'Six Caps',
      'Slackey',
      'Smokum',
      'Smythe',
      'Sniglet',
      'Snippet',
      'Sofia',
      'Sonsie One',
      'Sorts Mill Goudy',
      'Special Elite',
      'Spicy Rice',
      'Spinnaker',
      'Spirax',
      'Squada One',
      'Stardos Stencil',
      'Stint Ultra Condensed',
      'Stint Ultra Expanded',
      'Stoke',
      'Sue Ellen Francisco',
      'Sunshiney',
      'Supermercado One',
      'Suwannaphum',
      'Swanky and Moo Moo',
      'Syncopate',
      'Tangerine',
      'Taprom',
      'Telex',
      'Tenor Sans',
      'The Girl Next Door',
      'Tienne',
      'Tinos',
      'Titan One',
      'Trade Winds',
      'Trocchi',
      'Trochut',
      'Trykker',
      'Tulpen One',
      'Ubuntu',
      'Ubuntu Condensed',
      'Ubuntu Mono',
      'Ultra',
      'Uncial Antiqua',
      'UnifrakturCook',
      'UnifrakturMaguntia',
      'Unkempt',
      'Unlock',
      'Unna',
      'VT323',
      'Varela',
      'Varela Round',
      'Vast Shadow',
      'Vibur',
      'Vidaloka',
      'Viga',
      'Voces',
      'Volkhov',
      'Vollkorn',
      'Voltaire',
      'Waiting for the Sunrise',
      'Wallpoet',
      'Walter Turncoat',
      'Wellfleet',
      'Wire One',
      'Yanone Kaffeesatz',
      'Yellowtail',
      'Yeseva One',
      'Yesteryear',
      'Zeyada',
];

    $fonts = array_values(array_unique(array_filter(array_map('strval', $fonts))));
    natcasesort($fonts);
    return array_values($fonts);
  }
  public function getSystemFontInlineCss(string $font): string
  {
    $font = trim($font);
    if ($font === '' || strtolower($font) === 'default') {
      return '';
    }

    $fonts = $this->getEmailFonts(false);
    return isset($fonts[$font]) ? (string) $fonts[$font] : '';
  }

  public function getFontFamilyCss($font): string
  {
    $font = trim((string) $font);
    if ($font === '' || strtolower($font) === 'default') {
      return '';
    }

    if (substr($font, 0, 7) === 'google:') {
      $fontName = trim(substr($font, 7));
      if ($fontName === '') {
        return '';
      }
      $this->requestGoogleFont($fontName);
      return 'font-family: "' . esc_attr($fontName) . '", sans-serif;';
    }

    return $this->getSystemFontInlineCss($font);
  }

  public function requestGoogleFont(string $font): void
  {
    $font = trim($font);
    if ($font === '') {
      return;
    }
    $this->requestedGoogleFonts[$font] = $font;
  }

  public function getGoogleFontImportCss(bool $flush = false): string
  {
    if (empty($this->requestedGoogleFonts)) {
      return '';
    }

    $css = '';
    foreach ($this->requestedGoogleFonts as $font) {
      $css .= "@import url('https://fonts.googleapis.com/css?family=" . esc_attr(urlencode($font)) . "&display=swap');";
    }

    if ($flush) {
      $this->requestedGoogleFonts = [];
    }

    return $css;
  }

  public function textarea($name, $params = ['attrs' => '', 'value' => ''])
  {
    $attrs = isset($params['attrs']) ? $params['attrs'] : '';
    $value = isset($params['value']) ? esc_textarea($params['value']) : '';
    // Attribute fragments are built internally by the component callers.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<textarea name="' . esc_attr($this->namePrefix) . esc_attr($name) . '" ' . $attrs . '>' . $value . '</textarea>';
  }

  public function radio($name, $params = ['options' => [], 'attrs' => '', 'checked' => ''])
  {
    foreach ($params['options'] as $value => $label) {
      $checked = checked($params['checked'], $value, false);
      echo '<input type="radio" name="' . esc_attr($this->namePrefix) . esc_attr($name) . '" value="' . esc_attr($value) . '" ' . esc_attr($checked) . ' ' . esc_attr($params['attrs']) . '>';
      echo esc_html($label) . '<br>';
    }
  }

  public function file($name, $params = ['attrs' => '', 'multiple' => false])
  {
    $attrs = isset($params['attrs']) ? esc_attr($params['attrs']) : '';
    $multiple = isset($params['multiple']) && $params['multiple'] ? 'multiple' : '';
    $name = $params['multiple'] ? $name . '[]' : $name;
    echo '<input type="file" name="' . esc_attr($this->namePrefix) . esc_attr($name) . '" ' . esc_attr($attrs) . ' ' . esc_attr($multiple) . ' />';
  }

  public function pageDropdown($name, $params = ['attrs' => '', 'selected' => '', 'show_option_none' => 'Select a page'])
  {
    $args = [
      'name' => esc_attr($name),
      'selected' => esc_attr($params['selected']),
      'echo' => 0,
      'show_option_none' => esc_attr($params['show_option_none']),
      'option_none_value' => '',
      'class' => esc_attr($params['attrs']),
    ];
    echo esc_html(wp_dropdown_pages($args));
  }

  public function colorpicker($name, $params = ['value' => ''])
  {
    $this->loadColorPicker();
    $value = isset($params['value']) ? $params['value'] : '';
    echo '<div class="ppsgen-color-picker">';
    $this->text('', [
      'value' => $value,
      'attrs' => 'data-alpha-enabled="true" data-alpha="true" class="ppsgen-color-result wp-color-picker color-picker"',
    ]);
    $this->text($name, [
      'value' => $value,
      'attrs' => 'class="ppsgen-color-result-text ppsgenColorInputText"',
    ]);
    echo '</div>';
  }

  public function value($val, $else = '')
  {
    $val = isset($val) ? esc_attr($val) : esc_attr($else);
    return $val;
  }

  public function cvalue($val, $else = '')
  {
    $val = !empty($val) ? str_replace('percent', '%', $val) : '';
    $val = !empty($val) ? esc_attr($val) : esc_attr($else);
    return $val;
  }

  public function nvalue($value)
  {
    return $value === null || $value === 'null' ? '' : $value;
  }

  public function getAdminPaginator(int $page, int $totalPages): string
  {
    if ($totalPages <= 1) {
      return '';
    }
    $range = 2;
    $start = max(2, $page - $range);
    $end = min($totalPages - 1, $page + $range);
    ob_start();
    ?>
<div class="tablenav ppsgen-pagination">
  <button name="page" value="1" <?php disabled(1, $page); ?>>
    <?php echo esc_html(1); ?>
  </button>
  <?php if ($start > 2): ?>
  <span class="ppsgen-pagination-dots">&hellip;</span>
  <?php endif; ?>
  <?php for ($i = $start; $i <= $end; $i++): ?>
  <button name="page" value="<?php ppsgen_esc_attr($i); ?>" <?php disabled($i, $page); ?>>
    <?php echo esc_html($i); ?>
  </button>
  <?php endfor; ?>
  <?php if ($end < $totalPages - 1): ?>
  <span class="ppsgen-pagination-dots">&hellip;</span>
  <?php endif; ?>
  <button name="page" value="<?php ppsgen_esc_attr($totalPages); ?>" <?php disabled($totalPages, $page); ?>>
    <?php echo esc_html($totalPages); ?>
  </button>
</div>
<?php return ob_get_clean();
  }

  public function getAdminTableTotal(int $start, int $end, int $total): string
  {
    ob_start(); ?>
<div class="tablenav top">
  <div class="tablenav-pages">
    <span class="displaying-num">
      <?php printf(
        // translators: %1$d = start entry number, %2$d = end entry number, %3$d = total entries
        esc_html__('Showing %1$d to %2$d of %3$d entries', 'ppsgen'),
        esc_html($start),
        esc_html($end),
        esc_html($total),
      ); ?>
    </span>
  </div>
</div>
<?php return ob_get_clean();
  }

  public function getAdminTableLimitFilter($order_by, $order_dir, $per_page): string
  {
    ob_start(); ?>
<input type="hidden" name="order_by" id="order_by" value="<?php ppsgen_esc_attr($order_by); ?>">
<input type="hidden" name="order_dir" id="order_dir" value="<?php ppsgen_esc_attr($order_dir); ?>">
<label><?php esc_html_e('Show per page:', 'ppsgen'); ?></label>
<select name="per_page" onchange="this.form.submit()">
  <?php foreach ([10, 20, 50, 100, 500] as $n) { ?>
  <option value="<?php ppsgen_esc_attr($n); ?>" <?php selected($n, $per_page); ?>><?php ppsgen_esc_attr($n); ?></option>
  <?php } ?>
</select>
<button class="ppsgenTableListBtn ppsgenTableListSearch" type="submit"><span class="dashicons dashicons-search"></span> <?php esc_html_e('Apply Filters', 'ppsgen'); ?></button>
<?php return ob_get_clean();
  }

  public function initFrontendScripts()
  {
    wp_register_style('ppsgen-frontend', PPSGEN_PLUGIN_URL . 'public/assets/css/ppsgen.frontend.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-frontend');
    wp_register_script('ppsgen-frontend', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-frontend');
    wp_localize_script('ppsgen-frontend', 'ppsgenFrontendAjax', [
      'url' => admin_url('admin-ajax.php'),
      //'admin_url' => admin_url('admin.php'),
      'prefix' => 'ppsgen',
      'cookiePath' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
      'cfvNotices' => [
        'captchaRequired' => esc_html__('Captcha is required.', 'ppsgen'),
        'required' => esc_html__('This field is required.', 'ppsgen'),
      ],
      'cfvInputMaskDef' => $this->getInputMaskDef(),
    ]);
  }

  public function loadRecaptchaV2()
  {
    wp_enqueue_script('ppsgen-recaptcha-v2', 'https://www.google.com/recaptcha/api.js?onload=ppsgenOnloadCallback&render=explicit', [], PPSGEN_VERSION, true);
  }

  public function loadSortable()
  {
    wp_register_script('ppsgen-backend-sortable', PPSGEN_PLUGIN_URL . 'public/assets/libs/sortable/sortable.min.js', [], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-sortable');
    wp_register_script('ppsgen-backend-sortable-jquery', PPSGEN_PLUGIN_URL . 'public/assets/libs/sortable/jquery-sortable.js', ['jquery', 'ppsgen-backend-sortable'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-sortable-jquery');
  }

  public function loadPpsgenselect()
  {
    wp_register_style('ppsgen-ppsgenselect', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenselect/ppsgenselect.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-ppsgenselect');
    wp_register_script('ppsgen-ppsgenselect', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenselect/ppsgenselect.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-ppsgenselect');
  }

  public function loadInputmask()
  {
    wp_register_script('ppsgen-inputmask-core', PPSGEN_PLUGIN_URL . 'public/assets/libs/inputmask/inputmask.min.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-inputmask-core');
    wp_register_script('ppsgen-inputmask-jquery', PPSGEN_PLUGIN_URL . 'public/assets/libs/inputmask/jquery.inputmask.min.js', ['ppsgen-inputmask-core'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-inputmask-jquery');
    if (is_admin()) {
      wp_add_inline_script(
        'ppsgen-frontend',
        "jQuery(document).ready(function () {
        Inputmask.extendDefinitions(ppsgenAjax.cfvInputMaskDef);
        jQuery('input[data-mask]').each(function () {
          const mask = jQuery(this).data('mask');
          if (mask) {
            new Inputmask({
              mask: mask,
              regex: true,
            }).mask(this);
          }
        });
      });",
      );
    } else {
      wp_add_inline_script(
        'ppsgen-frontend',
        "jQuery(document).ready(function () {
        Inputmask.extendDefinitions(ppsgenFrontendAjax.cfvInputMaskDef);
        jQuery('input[data-mask]').each(function () {
          const mask = jQuery(this).data('mask');
          if (mask) {
            new Inputmask({
              mask: mask,
              regex: true,
            }).mask(this);
          }
        });
      });",
      );
    }
  }

  public function loadAnimate()
  {
    wp_register_style('ppsgen-animate', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenanimate/ppsgenanimate.min.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-animate');
  }

  public function loadFA()
  {
    if (is_admin()) {
      wp_register_script('ppsgen-fa-icons-list', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenfa/ppsgen-fa-icons-shortlist.js', [], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-fa-icons-list');
      wp_register_style('ppsgen-fa', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenfa/ppsgen-fa-all.min.css', [], PPSGEN_VERSION);
      wp_enqueue_style('ppsgen-fa');
    } else {
      wp_register_style('ppsgen-fa', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenfa/ppsgen-fa-all.min.css', [], PPSGEN_VERSION);
      wp_enqueue_style('ppsgen-fa');
    }
  }

  public function loadUndr()
  {
    if (is_admin()) {
      wp_register_script('ppsgen-svg-undraw-icons-list', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgensvg/undraw/list.js', [], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-svg-undraw-icons-list');
    }
  }

  public function loadColorPicker()
  {
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('wp-color-picker');
    wp_add_inline_script('wp-color-picker', 'var wpColorPickerL10n = [];');
    wp_register_script('ppsgen-wp-color-picker-alpha', PPSGEN_PLUGIN_URL . 'public/assets/libs/colorpicker/colorpicker.js', ['wp-color-picker'], PPSGEN_VERSION, false);
    wp_enqueue_script('ppsgen-wp-color-picker-alpha');
  }

  public function getTableFontUnits($onlyPx = false)
  {
    if ($onlyPx) {
      return [
        'px' => esc_attr__('px', 'ppsgen'),
      ];
    }
    return [
      'px' => esc_attr__('px', 'ppsgen'),
      'em' => esc_attr__('em', 'ppsgen'),
    ];
  }

  public function getTableUnits()
  {
    return $units = [
      'px' => esc_attr__('px', 'ppsgen'),
      'percent' => esc_attr__('%', 'ppsgen'),
    ];
  }

  public function backdropFilterUnits()
  {
    return $units = [
      'blur' => esc_attr__('blur', 'ppsgen'),
      'brightness' => esc_attr__('brightness', 'ppsgen'),
      'contrast' => esc_attr__('contrast', 'ppsgen'),
      'grayscale' => esc_attr__('grayscale', 'ppsgen'),
      'invert' => esc_attr__('invert', 'ppsgen'),
      'opacity' => esc_attr__('opacity', 'ppsgen'),
      'sepia' => esc_attr__('sepia', 'ppsgen'),
      'saturate' => esc_attr__('saturate', 'ppsgen'),
    ];
  }

  public function getUnits()
  {
    return $units = [
      'px' => esc_attr__('px', 'ppsgen'),
      'percent' => esc_attr__('%', 'ppsgen'),
      'em' => esc_attr__('em', 'ppsgen'),
      'rem' => esc_attr__('rem', 'ppsgen'),
      'vw' => esc_attr__('vw', 'ppsgen'),
      'vh' => esc_attr__('vh', 'ppsgen'),
      'vmin' => esc_attr__('vmin', 'ppsgen'),
      'vmax' => esc_attr__('vmax', 'ppsgen'),
      'pt' => esc_attr__('pt', 'ppsgen'),
      'pc' => esc_attr__('pc', 'ppsgen'),
      'cm' => esc_attr__('cm', 'ppsgen'),
      'mm' => esc_attr__('mm', 'ppsgen'),
      'in' => esc_attr__('in', 'ppsgen'),
    ];
  }

  public function getTextAlign()
  {
    return $units = [
      '' => esc_attr__('Align', 'ppsgen'),
      'left' => esc_attr__('left', 'ppsgen'),
      'right' => esc_attr__('right', 'ppsgen'),
      'center' => esc_attr__('center', 'ppsgen'),
      'justify' => esc_attr__('justify', 'ppsgen'),
    ];
  }

  public function getFontStyles()
  {
    return $units = [
      '' => esc_attr__('Font Style', 'ppsgen'),
      'normal' => esc_attr__('normal', 'ppsgen'),
      'italic' => esc_attr__('italic', 'ppsgen'),
      'inherit' => esc_attr__('inherit', 'ppsgen'),
      'initial' => esc_attr__('initial', 'ppsgen'),
    ];
  }

  public function getRadiusStyles()
  {
    return $units = [
      '' => '',
      'square' => esc_attr__('square', 'ppsgen'),
      'rounded' => esc_attr__('rounded', 'ppsgen'),
      'circle' => esc_attr__('circle', 'ppsgen'),
    ];
  }

  public function getFontWeight()
  {
    return $units = [
      '' => esc_attr__('Font Weight', 'ppsgen'),
      'normal' => esc_attr__('normal', 'ppsgen'),
      'bold' => esc_attr__('bold', 'ppsgen'),
      'lighter' => esc_attr__('lighter', 'ppsgen'),
      'bolder' => esc_attr__('bolder', 'ppsgen'),
      '100' => esc_attr__('100', 'ppsgen'),
      '200' => esc_attr__('200', 'ppsgen'),
      '300' => esc_attr__('300', 'ppsgen'),
      '400' => esc_attr__('400', 'ppsgen'),
      '500' => esc_attr__('500', 'ppsgen'),
      '600' => esc_attr__('600', 'ppsgen'),
      '700' => esc_attr__('700', 'ppsgen'),
      '800' => esc_attr__('800', 'ppsgen'),
      '900' => esc_attr__('900', 'ppsgen'),
    ];
  }

  public function getColumns()
  {
    return $units = [
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
      '6' => '6',
      '7' => '7',
      '8' => '8',
      '9' => '9',
      '10' => '10',
      '11' => '11',
      '12' => '12',
    ];
  }

  public function getLanguages()
  {
    return [
      'af' => esc_html__('Afrikaans', 'ppsgen'),
      'am' => esc_html__('Amharic', 'ppsgen'),
      'an' => esc_html__('Aragonese', 'ppsgen'),
      'ar' => esc_html__('Arabic', 'ppsgen'),
      'ary' => esc_html__('Moroccan Arabic', 'ppsgen'),
      'as' => esc_html__('Assamese', 'ppsgen'),
      'az' => esc_html__('South Azerbaijani', 'ppsgen'),
      'aze' => esc_html__('Azerbaijani', 'ppsgen'),
      'be' => esc_html__('Belarusian', 'ppsgen'),
      'bg' => esc_html__('Bulgarian', 'ppsgen'),
      'bn' => esc_html__('Bengali', 'ppsgen'),
      'bo' => esc_html__('Tibetan', 'ppsgen'),
      'bs' => esc_html__('Bosnian', 'ppsgen'),
      'ca' => esc_html__('Catalan', 'ppsgen'),
      'ceb' => esc_html__('Cebuano', 'ppsgen'),
      'cs' => esc_html__('Czech', 'ppsgen'),
      'cy' => esc_html__('Welsh', 'ppsgen'),
      'da' => esc_html__('Danish', 'ppsgen'),
      'de' => esc_html__('German (Switzerland)', 'ppsgen'),
      'de_DE' => esc_html__('German', 'ppsgen'),
      'de_DE_formal' => esc_html__('German (Formal)', 'ppsgen'),
      'de_CH_informal' => esc_html__('German (Switzerland, Informal)', 'ppsgen'),
      'de_AT' => esc_html__('German (Austria)', 'ppsgen'),
      'dsb' => esc_html__('Lower Sorbian', 'ppsgen'),
      'dz' => esc_html__('Dzongkha', 'ppsgen'),
      'el' => esc_html__('Greek', 'ppsgen'),
      'en' => esc_html__('English (South Africa)', 'ppsgen'),
      'eng' => esc_html__('English (UK)', 'ppsgen'),
      'en_AU' => esc_html__('English (Australia)', 'ppsgen'),
      'en_CA' => esc_html__('English (Canada)', 'ppsgen'),
      'en_NZ' => esc_html__('English (New Zealand)', 'ppsgen'),
      'eo' => esc_html__('Esperanto', 'ppsgen'),
      'es' => esc_html__('Spanish (Costa Rica)', 'ppsgen'),
      'spa' => esc_html__('Spanish (Peru)', 'ppsgen'),
      'es_CL' => esc_html__('Spanish (Chile)', 'ppsgen'),
      'es_AR' => esc_html__('Spanish (Argentina)', 'ppsgen'),
      'es_CO' => esc_html__('Spanish (Colombia)', 'ppsgen'),
      'es_ES' => esc_html__('Spanish', 'ppsgen'),
      'es_VE' => esc_html__('Spanish (Venezuela)', 'ppsgen'),
      'es_EC' => esc_html__('Spanish (Ecuador)', 'ppsgen'),
      'es_DO' => esc_html__('Spanish (Dominican Republic)', 'ppsgen'),
      'es_UY' => esc_html__('Spanish (Uruguay)', 'ppsgen'),
      'es_PR' => esc_html__('Spanish (Puerto Rico)', 'ppsgen'),
      'es_MX' => esc_html__('Spanish (Mexico)', 'ppsgen'),
      'es_GT' => esc_html__('Spanish (Guatemala)', 'ppsgen'),
      'et' => esc_html__('Estonian', 'ppsgen'),
      'eu' => esc_html__('Basque', 'ppsgen'),
      'fa' => esc_html__('Persian (Afghanistan)', 'ppsgen'),
      'fas' => esc_html__('Persian', 'ppsgen'),
      'fi' => esc_html__('Finnish', 'ppsgen'),
      'fr' => esc_html__('French (Canada)', 'ppsgen'),
      'fr_FR' => esc_html__('French', 'ppsgen'),
      'fra' => esc_html__('French (Belgium)', 'ppsgen'),
      'fur' => esc_html__('Friulian', 'ppsgen'),
      'fy' => esc_html__('Frisian', 'ppsgen'),
      'gd' => esc_html__('Scottish Gaelic', 'ppsgen'),
      'gl' => esc_html__('Galician', 'ppsgen'),
      'gu' => esc_html__('Gujarati', 'ppsgen'),
      'haz' => esc_html__('Hazaragi', 'ppsgen'),
      'he' => esc_html__('Hebrew', 'ppsgen'),
      'hi' => esc_html__('Hindi', 'ppsgen'),
      'hr' => esc_html__('Croatian', 'ppsgen'),
      'hsb' => esc_html__('Upper Sorbian', 'ppsgen'),
      'hu' => esc_html__('Hungarian', 'ppsgen'),
      'hy' => esc_html__('Armenian', 'ppsgen'),
      'id' => esc_html__('Indonesian', 'ppsgen'),
      'is' => esc_html__('Icelandic', 'ppsgen'),
      'it' => esc_html__('Italian', 'ppsgen'),
      'ja' => esc_html__('Japanese', 'ppsgen'),
      'jv' => esc_html__('Javanese', 'ppsgen'),
      'ka' => esc_html__('Georgian', 'ppsgen'),
      'kab' => esc_html__('Kabyle', 'ppsgen'),
      'kk' => esc_html__('Kazakh', 'ppsgen'),
      'km' => esc_html__('Khmer', 'ppsgen'),
      'kn' => esc_html__('Kannada', 'ppsgen'),
      'ko' => esc_html__('Korean', 'ppsgen'),
      'ku' => esc_html__('Kurdish', 'ppsgen'),
      'ky' => esc_html__('Kyrgyz', 'ppsgen'),
      'lo' => esc_html__('Lao', 'ppsgen'),
      'lt' => esc_html__('Lithuanian', 'ppsgen'),
      'lv' => esc_html__('Latvian', 'ppsgen'),
      'mk' => esc_html__('Macedonian', 'ppsgen'),
      'ml' => esc_html__('Malayalam', 'ppsgen'),
      'mn' => esc_html__('Mongolian', 'ppsgen'),
      'mr' => esc_html__('Marathi', 'ppsgen'),
      'ms' => esc_html__('Malay', 'ppsgen'),
      'my' => esc_html__('Burmese', 'ppsgen'),
      'nb' => esc_html__('Norwegian BokmГҐl', 'ppsgen'),
      'ne' => esc_html__('Nepali', 'ppsgen'),
      'nl' => esc_html__('Dutch (Belgium)', 'ppsgen'),
      'nld' => esc_html__('Dutch', 'ppsgen'),
      'nl_NL_formal' => esc_html__('Dutch (Formal)', 'ppsgen'),
      'nn' => esc_html__('Norwegian Nynorsk', 'ppsgen'),
      'oc' => esc_html__('Occitan', 'ppsgen'),
      'pa' => esc_html__('Punjabi', 'ppsgen'),
      'pl' => esc_html__('Polish', 'ppsgen'),
      'ps' => esc_html__('Pashto', 'ppsgen'),
      'pt' => esc_html__('Portuguese (Brazil)', 'ppsgen'),
      'pt_PT' => esc_html__('Portuguese', 'ppsgen'),
      'pt_PT_ao90' => esc_html__('Portuguese (AO90)', 'ppsgen'),
      'pt_AO' => esc_html__('Portuguese (Angola)', 'ppsgen'),
      'rhg' => esc_html__('Rohingya', 'ppsgen'),
      'ro' => esc_html__('Romanian', 'ppsgen'),
      'ru' => esc_html__('Russian', 'ppsgen'),
      'sah' => esc_html__('Sakha', 'ppsgen'),
      'sd' => esc_html__('Sindhi', 'ppsgen'),
      'si' => esc_html__('Sinhala', 'ppsgen'),
      'sk' => esc_html__('Slovak', 'ppsgen'),
      'skr' => esc_html__('Saraiki', 'ppsgen'),
      'sl' => esc_html__('Slovenian', 'ppsgen'),
      'sq' => esc_html__('Albanian', 'ppsgen'),
      'sr' => esc_html__('Serbian', 'ppsgen'),
      'sv' => esc_html__('Swedish', 'ppsgen'),
      'sw' => esc_html__('Swahili', 'ppsgen'),
      'szl' => esc_html__('Silesian', 'ppsgen'),
      'ta' => esc_html__('Tamil', 'ppsgen'),
      'tam' => esc_html__('Tamil', 'ppsgen'),
      'te' => esc_html__('Telugu', 'ppsgen'),
      'th' => esc_html__('Thai', 'ppsgen'),
      'tl' => esc_html__('Tagalog', 'ppsgen'),
      'tr' => esc_html__('Turkish', 'ppsgen'),
      'tt' => esc_html__('Tatar', 'ppsgen'),
      'ty' => esc_html__('Tahitian', 'ppsgen'),
      'ug' => esc_html__('Uyghur', 'ppsgen'),
      'uk' => esc_html__('Ukrainian', 'ppsgen'),
      'ur' => esc_html__('Urdu', 'ppsgen'),
      'uz' => esc_html__('Uzbek', 'ppsgen'),
      'vi' => esc_html__('Vietnamese', 'ppsgen'),
      'zh' => esc_html__('Chinese (Simplified)', 'ppsgen'),
      'zho' => esc_html__('Chinese (Traditional)', 'ppsgen'),
      'zh_HK' => esc_html__('Chinese (Hong Kong)', 'ppsgen'),
    ];
  }

  public function getCountries()
  {
    if (function_exists('WC')) {
      $countries = WC()->countries->get_countries();
      foreach ($countries as $code => $name) {
        $countries[$code] = $name;
      }
      return $countries;
    }
    return [
      'AF' => esc_html__('Afghanistan', 'ppsgen'),
      'AL' => esc_html__('Albania', 'ppsgen'),
      'DZ' => esc_html__('Algeria', 'ppsgen'),
      'AS' => esc_html__('American Samoa', 'ppsgen'),
      'AD' => esc_html__('Andorra', 'ppsgen'),
      'AO' => esc_html__('Angola', 'ppsgen'),
      'AI' => esc_html__('Anguilla', 'ppsgen'),
      'AQ' => esc_html__('Antarctica', 'ppsgen'),
      'AG' => esc_html__('Antigua and Barbuda', 'ppsgen'),
      'AR' => esc_html__('Argentina', 'ppsgen'),
      'AM' => esc_html__('Armenia', 'ppsgen'),
      'AW' => esc_html__('Aruba', 'ppsgen'),
      'AU' => esc_html__('Australia', 'ppsgen'),
      'AT' => esc_html__('Austria', 'ppsgen'),
      'AZ' => esc_html__('Azerbaijan', 'ppsgen'),
      'BS' => esc_html__('Bahamas', 'ppsgen'),
      'BH' => esc_html__('Bahrain', 'ppsgen'),
      'BD' => esc_html__('Bangladesh', 'ppsgen'),
      'BB' => esc_html__('Barbados', 'ppsgen'),
      'BY' => esc_html__('Belarus', 'ppsgen'),
      'BE' => esc_html__('Belgium', 'ppsgen'),
      'BZ' => esc_html__('Belize', 'ppsgen'),
      'BJ' => esc_html__('Benin', 'ppsgen'),
      'BM' => esc_html__('Bermuda', 'ppsgen'),
      'BT' => esc_html__('Bhutan', 'ppsgen'),
      'BO' => esc_html__('Bolivia', 'ppsgen'),
      'BA' => esc_html__('Bosnia and Herzegowina', 'ppsgen'),
      'BW' => esc_html__('Botswana', 'ppsgen'),
      'BV' => esc_html__('Bouvet Island', 'ppsgen'),
      'BR' => esc_html__('Brazil', 'ppsgen'),
      'IO' => esc_html__('British Indian Ocean Territory', 'ppsgen'),
      'BN' => esc_html__('Brunei Darussalam', 'ppsgen'),
      'BG' => esc_html__('Bulgaria', 'ppsgen'),
      'BF' => esc_html__('Burkina Faso', 'ppsgen'),
      'BI' => esc_html__('Burundi', 'ppsgen'),
      'KH' => esc_html__('Cambodia', 'ppsgen'),
      'CM' => esc_html__('Cameroon', 'ppsgen'),
      'CA' => esc_html__('Canada', 'ppsgen'),
      'CV' => esc_html__('Cape Verde', 'ppsgen'),
      'KY' => esc_html__('Cayman Islands', 'ppsgen'),
      'CF' => esc_html__('Central African Republic', 'ppsgen'),
      'TD' => esc_html__('Chad', 'ppsgen'),
      'CL' => esc_html__('Chile', 'ppsgen'),
      'CN' => esc_html__('China', 'ppsgen'),
      'CX' => esc_html__('Christmas Island', 'ppsgen'),
      'CC' => esc_html__('Cocos (Keeling) Islands', 'ppsgen'),
      'CO' => esc_html__('Colombia', 'ppsgen'),
      'KM' => esc_html__('Comoros', 'ppsgen'),
      'CG' => esc_html__('Congo', 'ppsgen'),
      'CK' => esc_html__('Cook Islands', 'ppsgen'),
      'CR' => esc_html__('Costa Rica', 'ppsgen'),
      'CI' => esc_html__('Cote D\'Ivoire', 'ppsgen'),
      'HR' => esc_html__('Croatia', 'ppsgen'),
      'CU' => esc_html__('Cuba', 'ppsgen'),
      'CY' => esc_html__('Cyprus', 'ppsgen'),
      'CZ' => esc_html__('Czech Republic', 'ppsgen'),
      'DK' => esc_html__('Denmark', 'ppsgen'),
      'DJ' => esc_html__('Djibouti', 'ppsgen'),
      'DM' => esc_html__('Dominica', 'ppsgen'),
      'DO' => esc_html__('Dominican Republic', 'ppsgen'),
      'TP' => esc_html__('East Timor', 'ppsgen'),
      'EC' => esc_html__('Ecuador', 'ppsgen'),
      'EG' => esc_html__('Egypt', 'ppsgen'),
      'SV' => esc_html__('El Salvador', 'ppsgen'),
      'GQ' => esc_html__('Equatorial Guinea', 'ppsgen'),
      'ER' => esc_html__('Eritrea', 'ppsgen'),
      'EE' => esc_html__('Estonia', 'ppsgen'),
      'ET' => esc_html__('Ethiopia', 'ppsgen'),
      'FK' => esc_html__('Falkland Islands (Malvinas)', 'ppsgen'),
      'FO' => esc_html__('Faroe Islands', 'ppsgen'),
      'FJ' => esc_html__('Fiji', 'ppsgen'),
      'FI' => esc_html__('Finland', 'ppsgen'),
      'FR' => esc_html__('France', 'ppsgen'),
      'FX' => esc_html__('France, Metropolitan', 'ppsgen'),
      'GF' => esc_html__('French Guiana', 'ppsgen'),
      'PF' => esc_html__('French Polynesia', 'ppsgen'),
      'TF' => esc_html__('French Southern Territories', 'ppsgen'),
      'GA' => esc_html__('Gabon', 'ppsgen'),
      'GM' => esc_html__('Gambia', 'ppsgen'),
      'GE' => esc_html__('Georgia', 'ppsgen'),
      'DE' => esc_html__('Germany', 'ppsgen'),
      'GH' => esc_html__('Ghana', 'ppsgen'),
      'GI' => esc_html__('Gibraltar', 'ppsgen'),
      'GR' => esc_html__('Greece', 'ppsgen'),
      'GL' => esc_html__('Greenland', 'ppsgen'),
      'GD' => esc_html__('Grenada', 'ppsgen'),
      'GP' => esc_html__('Guadeloupe', 'ppsgen'),
      'GU' => esc_html__('Guam', 'ppsgen'),
      'GT' => esc_html__('Guatemala', 'ppsgen'),
      'GN' => esc_html__('Guinea', 'ppsgen'),
      'GW' => esc_html__('Guinea-bissau', 'ppsgen'),
      'GY' => esc_html__('Guyana', 'ppsgen'),
      'HT' => esc_html__('Haiti', 'ppsgen'),
      'HM' => esc_html__('Heard and Mc Donald Islands', 'ppsgen'),
      'HN' => esc_html__('Honduras', 'ppsgen'),
      'HK' => esc_html__('Hong Kong', 'ppsgen'),
      'HU' => esc_html__('Hungary', 'ppsgen'),
      'IS' => esc_html__('Iceland', 'ppsgen'),
      'IN' => esc_html__('India', 'ppsgen'),
      'ID' => esc_html__('Indonesia', 'ppsgen'),
      'IR' => esc_html__('Iran (Islamic Republic of)', 'ppsgen'),
      'IQ' => esc_html__('Iraq', 'ppsgen'),
      'IE' => esc_html__('Ireland', 'ppsgen'),
      'IL' => esc_html__('Israel', 'ppsgen'),
      'IT' => esc_html__('Italy', 'ppsgen'),
      'JM' => esc_html__('Jamaica', 'ppsgen'),
      'JP' => esc_html__('Japan', 'ppsgen'),
      'JO' => esc_html__('Jordan', 'ppsgen'),
      'KZ' => esc_html__('Kazakhstan', 'ppsgen'),
      'KE' => esc_html__('Kenya', 'ppsgen'),
      'KI' => esc_html__('Kiribati', 'ppsgen'),
      'KP' => esc_html__('Korea, Democratic People\'s Republic of', 'ppsgen'),
      'KR' => esc_html__('Korea, Republic of', 'ppsgen'),
      'KW' => esc_html__('Kuwait', 'ppsgen'),
      'KG' => esc_html__('Kyrgyzstan', 'ppsgen'),
      'LA' => esc_html__('Lao People\'s Democratic Republic', 'ppsgen'),
      'LV' => esc_html__('Latvia', 'ppsgen'),
      'LB' => esc_html__('Lebanon', 'ppsgen'),
      'LS' => esc_html__('Lesotho', 'ppsgen'),
      'LR' => esc_html__('Liberia', 'ppsgen'),
      'LY' => esc_html__('Libyan Arab Jamahiriya', 'ppsgen'),
      'LI' => esc_html__('Liechtenstein', 'ppsgen'),
      'LT' => esc_html__('Lithuania', 'ppsgen'),
      'LU' => esc_html__('Luxembourg', 'ppsgen'),
      'MO' => esc_html__('Macau', 'ppsgen'),
      'MK' => esc_html__('Macedonia, The Former Yugoslav Republic of', 'ppsgen'),
      'MG' => esc_html__('Madagascar', 'ppsgen'),
      'MW' => esc_html__('Malawi', 'ppsgen'),
      'MY' => esc_html__('Malaysia', 'ppsgen'),
      'MV' => esc_html__('Maldives', 'ppsgen'),
      'ML' => esc_html__('Mali', 'ppsgen'),
      'MT' => esc_html__('Malta', 'ppsgen'),
      'MH' => esc_html__('Marshall Islands', 'ppsgen'),
      'MQ' => esc_html__('Martinique', 'ppsgen'),
      'MR' => esc_html__('Mauritania', 'ppsgen'),
      'MU' => esc_html__('Mauritius', 'ppsgen'),
      'YT' => esc_html__('Mayotte', 'ppsgen'),
      'MX' => esc_html__('Mexico', 'ppsgen'),
      'FM' => esc_html__('Micronesia, Federated States of', 'ppsgen'),
      'MD' => esc_html__('Moldova, Republic of', 'ppsgen'),
      'MC' => esc_html__('Monaco', 'ppsgen'),
      'MN' => esc_html__('Mongolia', 'ppsgen'),
      'ME' => esc_html__('Montenegro', 'ppsgen'),
      'MS' => esc_html__('Montserrat', 'ppsgen'),
      'MA' => esc_html__('Morocco', 'ppsgen'),
      'MZ' => esc_html__('Mozambique', 'ppsgen'),
      'MM' => esc_html__('Myanmar', 'ppsgen'),
      'NA' => esc_html__('Namibia', 'ppsgen'),
      'NR' => esc_html__('Nauru', 'ppsgen'),
      'NP' => esc_html__('Nepal', 'ppsgen'),
      'NL' => esc_html__('Netherlands', 'ppsgen'),
      'AN' => esc_html__('Netherlands Antilles', 'ppsgen'),
      'NC' => esc_html__('New Caledonia', 'ppsgen'),
      'NZ' => esc_html__('New Zealand', 'ppsgen'),
      'NI' => esc_html__('Nicaragua', 'ppsgen'),
      'NE' => esc_html__('Niger', 'ppsgen'),
      'NG' => esc_html__('Nigeria', 'ppsgen'),
      'NU' => esc_html__('Niue', 'ppsgen'),
      'NF' => esc_html__('Norfolk Island', 'ppsgen'),
      'MP' => esc_html__('Northern Mariana Islands', 'ppsgen'),
      'NO' => esc_html__('Norway', 'ppsgen'),
      'OM' => esc_html__('Oman', 'ppsgen'),
      'PK' => esc_html__('Pakistan', 'ppsgen'),
      'PW' => esc_html__('Palau', 'ppsgen'),
      'PA' => esc_html__('Panama', 'ppsgen'),
      'PG' => esc_html__('Papua New Guinea', 'ppsgen'),
      'PY' => esc_html__('Paraguay', 'ppsgen'),
      'PE' => esc_html__('Peru', 'ppsgen'),
      'PH' => esc_html__('Philippines', 'ppsgen'),
      'PN' => esc_html__('Pitcairn', 'ppsgen'),
      'PL' => esc_html__('Poland', 'ppsgen'),
      'PT' => esc_html__('Portugal', 'ppsgen'),
      'PR' => esc_html__('Puerto Rico', 'ppsgen'),
      'QA' => esc_html__('Qatar', 'ppsgen'),
      'RE' => esc_html__('Reunion', 'ppsgen'),
      'RO' => esc_html__('Romania', 'ppsgen'),
      'RU' => esc_html__('Russian Federation', 'ppsgen'),
      'RW' => esc_html__('Rwanda', 'ppsgen'),
      'KN' => esc_html__('Saint Kitts and Nevis', 'ppsgen'),
      'LC' => esc_html__('Saint Lucia', 'ppsgen'),
      'VC' => esc_html__('Saint Vincent and the Grenadines', 'ppsgen'),
      'WS' => esc_html__('Samoa', 'ppsgen'),
      'SM' => esc_html__('San Marino', 'ppsgen'),
      'ST' => esc_html__('Sao Tome and Principe', 'ppsgen'),
      'SA' => esc_html__('Saudi Arabia', 'ppsgen'),
      'SN' => esc_html__('Senegal', 'ppsgen'),
      'RS' => esc_html__('Serbia', 'ppsgen'),
      'SC' => esc_html__('Seychelles', 'ppsgen'),
      'SL' => esc_html__('Sierra Leone', 'ppsgen'),
      'SG' => esc_html__('Singapore', 'ppsgen'),
      'SK' => esc_html__('Slovakia (Slovak Republic)', 'ppsgen'),
      'SI' => esc_html__('Slovenia', 'ppsgen'),
      'SB' => esc_html__('Solomon Islands', 'ppsgen'),
      'SO' => esc_html__('Somalia', 'ppsgen'),
      'ZA' => esc_html__('South Africa', 'ppsgen'),
      'GS' => esc_html__('South Georgia and the South Sandwich Islands', 'ppsgen'),
      'ES' => esc_html__('Spain', 'ppsgen'),
      'LK' => esc_html__('Sri Lanka', 'ppsgen'),
      'SH' => esc_html__('St. Helena', 'ppsgen'),
      'PM' => esc_html__('St. Pierre and Miquelon', 'ppsgen'),
      'SD' => esc_html__('Sudan', 'ppsgen'),
      'SR' => esc_html__('Suriname', 'ppsgen'),
      'SJ' => esc_html__('Svalbard and Jan Mayen Islands', 'ppsgen'),
      'SZ' => esc_html__('Swaziland', 'ppsgen'),
      'SE' => esc_html__('Sweden', 'ppsgen'),
      'CH' => esc_html__('Switzerland', 'ppsgen'),
      'SY' => esc_html__('Syrian Arab Republic', 'ppsgen'),
      'TW' => esc_html__('Taiwan', 'ppsgen'),
      'TJ' => esc_html__('Tajikistan', 'ppsgen'),
      'TZ' => esc_html__('Tanzania, United Republic of', 'ppsgen'),
      'TH' => esc_html__('Thailand', 'ppsgen'),
      'TG' => esc_html__('Togo', 'ppsgen'),
      'TK' => esc_html__('Tokelau', 'ppsgen'),
      'TO' => esc_html__('Tonga', 'ppsgen'),
      'TT' => esc_html__('Trinidad and Tobago', 'ppsgen'),
      'TN' => esc_html__('Tunisia', 'ppsgen'),
      'TR' => esc_html__('Turkey', 'ppsgen'),
      'TM' => esc_html__('Turkmenistan', 'ppsgen'),
      'TC' => esc_html__('Turks and Caicos Islands', 'ppsgen'),
      'TV' => esc_html__('Tuvalu', 'ppsgen'),
      'UG' => esc_html__('Uganda', 'ppsgen'),
      'UA' => esc_html__('Ukraine', 'ppsgen'),
      'AE' => esc_html__('United Arab Emirates', 'ppsgen'),
      'GB' => esc_html__('United Kingdom', 'ppsgen'),
      'US' => esc_html__('United States', 'ppsgen'),
      'UM' => esc_html__('United States Minor Outlying Islands', 'ppsgen'),
      'UY' => esc_html__('Uruguay', 'ppsgen'),
      'UZ' => esc_html__('Uzbekistan', 'ppsgen'),
      'VU' => esc_html__('Vanuatu', 'ppsgen'),
      'VA' => esc_html__('Vatican City State (Holy See)', 'ppsgen'),
      'VE' => esc_html__('Venezuela', 'ppsgen'),
      'VN' => esc_html__('Viet Nam', 'ppsgen'),
      'VG' => esc_html__('Virgin Islands (British)', 'ppsgen'),
      'VI' => esc_html__('Virgin Islands (U.S.)', 'ppsgen'),
      'WF' => esc_html__('Wallis and Futuna Islands', 'ppsgen'),
      'EH' => esc_html__('Western Sahara', 'ppsgen'),
      'YE' => esc_html__('Yemen', 'ppsgen'),
      'ZR' => esc_html__('Zaire', 'ppsgen'),
      'ZM' => esc_html__('Zambia', 'ppsgen'),
      'ZW' => esc_html__('Zimbabwe', 'ppsgen'),
    ];
  }

  public function getEmailFonts($onlyName = false, $key = false)
  {
    $emailFonts = [
      'Default' => ' ',
      'Arial' => 'font-family: Arial, Helvetica, sans-serif;',
      'Arial Black' => 'font-family: Arial Black, Arial, Helvetica, sans-serif;',
      'Helvetica' => 'font-family: Helvetica, Arial, sans-serif;',
      'Tahoma' => 'font-family: Tahoma, Verdana, Segoe, sans-serif;',
      'Trebuchet MS' => 'font-family: Trebuchet MS, Helvetica, Arial, sans-serif;',
      'Verdana' => 'font-family: Verdana, Geneva, Tahoma, sans-serif;',
      'Lucida Sans Unicode' => 'font-family: Lucida Sans Unicode, Lucida Grande, Tahoma, sans-serif;',
      'Impact' => 'font-family: Impact, Charcoal, Arial Narrow Bold, sans-serif;',
      'Charcoal' => 'font-family: Charcoal, Lucida Grande, sans-serif;',
      'Times New Roman' => 'font-family: Times New Roman, Times, Georgia, serif;',
      'Georgia' => 'font-family: Georgia, Times New Roman, Times, serif;',
      'Palatino Linotype' => 'font-family: Palatino Linotype, Book Antiqua, Palatino, serif;',
      'Book Antiqua' => 'font-family: Book Antiqua, Palatino, Palatino Linotype, serif;',
      'Garamond' => 'font-family: Garamond, Times New Roman, serif;',
      'Bookman Old Style' => 'font-family: Bookman Old Style, Book Antiqua, serif;',
      'Courier New' => 'font-family: Courier New, Courier, Lucida Console, monospace;',
      'Courier' => 'font-family: Courier, Courier New, monospace;',
      'Lucida Console' => 'font-family: Lucida Console, Monaco, monospace;',
      'Monaco' => 'font-family: Monaco, Lucida Console, monospace;',
      'Comic Sans MS' => 'font-family: Comic Sans MS, Comic Sans, cursive, sans-serif;',
      'Webdings' => 'font-family: Webdings, Zapf Dingbats, sans-serif;',
      'Wingdings' => 'font-family: Wingdings, Webdings, sans-serif;',
      'Symbol' => 'font-family: Symbol, Zapf Dingbats, sans-serif;',
      'Zapf Dingbats' => 'font-family: Zapf Dingbats, Symbol, sans-serif;',
      'System UI' => 'font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif;',
      'Serif fallback' => 'font-family: Georgia, Times New Roman, Times, serif;',
      'Sans-serif fallback' => 'font-family: Arial, Helvetica, sans-serif;',
      'Monospace fallback' => 'font-family: Courier New, Courier, monospace;',
    ];
    if ($key) {
      return $emailFonts[$key];
    }
    if ($onlyName) {
      foreach ($emailFonts as $key => $value) {
        $emailFonts[$key] = $key;
      }
    }
    return $emailFonts;
  }

  public function getTextDecoration()
  {
    return $res = [
      'Default' => 'Default',
      'none' => 'None',
      'underline' => 'Underline',
      'line-through' => 'Line Through',
      'overline' => 'Overline',
      'underline overline' => 'Underline Overline',
    ];
  }

  public function getChainFieldOperators()
  {
    return $res = [
      'equals' => esc_attr__('Equals', 'ppsgen'),
      'not_equals' => esc_attr__('Not Equals', 'ppsgen'),
      'contains' => esc_attr__('Contains', 'ppsgen'),
      'not_contains' => esc_attr__('Does Not Contain', 'ppsgen'),
      'starts_with' => esc_attr__('Starts With', 'ppsgen'),
      'ends_with' => esc_attr__('Ends With', 'ppsgen'),
      'empty' => esc_attr__('Is Empty', 'ppsgen'),
      'not_empty' => esc_attr__('Is Not Empty', 'ppsgen'),
      'greater' => esc_attr__('Greater Than', 'ppsgen'),
      'greater_equal' => esc_attr__('Greater or Equal', 'ppsgen'),
      'less' => esc_attr__('Less Than', 'ppsgen'),
      'less_equal' => esc_attr__('Less or Equal', 'ppsgen'),
    ];
  }

  public function getInputMaskDef()
  {
    return $inputmaskDefinitions = [
      '9' => ['validator' => '[0-9]', 'cardinality' => 1],
      'a' => ['validator' => '[a-zA-Z]', 'cardinality' => 1],
      'A' => ['validator' => '[A-Z]', 'cardinality' => 1],
      'l' => ['validator' => '[a-z]', 'cardinality' => 1],
      'c' => ['validator' => '[Р°-СЏС‘]', 'cardinality' => 1, 'casing' => 'lower'],
      'C' => ['validator' => '[Рђ-РЇРЃ]', 'cardinality' => 1, 'casing' => 'upper'],
      'k' => ['validator' => '[Рђ-РЇР°-СЏРЃС‘]', 'cardinality' => 1],
      'u' => ['validator' => '\\p{L}', 'cardinality' => 1, 'regex' => true],
      '*' => ['validator' => '[a-zA-ZРђ-РЇР°-СЏРЃС‘0-9]', 'cardinality' => 1],
      '&' => ['validator' => '[A-ZРђ-РЇРЃ0-9]', 'cardinality' => 1],
      '#' => ['validator' => '[\\p{L}\\d!@#$%^&*()\\-_=+,.<>?]', 'cardinality' => 1, 'regex' => true],
    ];
  }
}

