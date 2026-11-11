#!/usr/bin/env python3
"""Genera els fitxers d'icones de theme_pspv a partir de @tabler/icons-webfont.

    npm install --prefix /tmp/tabler @tabler/icons-webfont
    python3 scripts/theme-icons.py /tmp/tabler/node_modules/@tabler/icons-webfont

Escriu:
  plugins/theme/pspv/classes/output/tabler_map.php   Font Awesome → Tabler (el que fa servir el sistema d'icones)
  plugins/theme/pspv/scss/pspv/_icons.scss           @font-face i les classes .ti-* que es fan servir
  plugins/theme/pspv/fonts/tabler-icons.woff2        la font

Per mapar una icona més, afegeix-la a MAP (nom de Font Awesome sense "fa-" → nom de Tabler
sense "ti-") i torna a executar l'script. Les que no són a MAP es continuen pintant amb
Font Awesome, que Moodle ja porta.
"""
import os
import re
import shutil
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, 'plugins', 'theme', 'pspv')

# Font Awesome 6 (com el fa servir Moodle 5.3) → Tabler Icons.
MAP = {
    'address-card': 'id', 'align-center': 'align-center', 'align-justify': 'align-justified', 'align-left': 'align-left',
    'align-right': 'align-right', 'anchor': 'anchor', 'angles-down': 'chevrons-down', 'angles-left': 'chevrons-left',
    'angles-right': 'chevrons-right', 'angles-up': 'chevrons-up', 'arrow-down': 'arrow-down',
    'arrow-down-long': 'arrow-narrow-down', 'arrow-down-short-wide': 'sort-ascending',
    'arrow-down-wide-short': 'sort-descending', 'arrow-left': 'arrow-left', 'arrow-right': 'arrow-right',
    'arrow-right-from-bracket': 'logout', 'arrow-right-to-bracket': 'login', 'arrow-rotate-left': 'rotate',
    'arrow-rotate-right': 'rotate-clockwise', 'arrow-up': 'arrow-up', 'arrow-up-from-bracket': 'upload',
    'arrow-up-long': 'arrow-narrow-up', 'arrow-up-right-from-square': 'external-link',
    'arrow-up-short-wide': 'sort-ascending', 'arrows-left-right': 'arrows-horizontal',
    'arrows-left-right-to-line': 'arrow-autofit-width', 'arrows-rotate': 'refresh', 'arrows-to-circle': 'focus-centered',
    'arrows-up-down': 'arrows-vertical', 'arrows-up-down-left-right': 'arrows-move', 'asterisk': 'asterisk',
    'award': 'award', 'ban': 'ban', 'bars': 'menu-2', 'bold': 'bold', 'bolt': 'bolt', 'book': 'book',
    'border-all': 'border-all', 'briefcase': 'briefcase', 'bullhorn': 'speakerphone', 'calculator': 'calculator',
    'calendar': 'calendar', 'calendar-check': 'calendar-check', 'calendar-plus': 'calendar-plus',
    'caret-down': 'caret-down', 'chart-bar': 'chart-bar', 'chart-column': 'chart-bar', 'chart-line': 'chart-line',
    'check': 'check', 'check-to-slot': 'checkbox', 'chevron-down': 'chevron-down', 'chevron-left': 'chevron-left',
    'chevron-right': 'chevron-right', 'chevron-up': 'chevron-up', 'circle-arrow-down': 'circle-arrow-down',
    'circle-check': 'circle-check', 'circle-exclamation': 'alert-circle', 'circle-info': 'info-circle',
    'circle-minus': 'circle-minus', 'circle-plus': 'circle-plus', 'circle-question': 'help-circle',
    'circle-user': 'user-circle', 'circle-xmark': 'circle-x', 'clipboard': 'clipboard',
    'clipboard-check': 'clipboard-check', 'clipboard-user': 'clipboard-text', 'clock': 'clock',
    'clock-rotate-left': 'history', 'cloud-upload': 'cloud-upload', 'code': 'code', 'cog': 'settings',
    'comment': 'message-circle', 'comments': 'messages', 'credit-card': 'credit-card', 'crosshairs': 'crosshair',
    'database': 'database', 'delete-left': 'backspace', 'dollar-sign': 'currency-dollar',
    'down-left-and-up-right-to-center': 'arrows-minimize', 'download': 'download', 'ear-listen': 'ear',
    'ellipsis': 'dots', 'ellipsis-vertical': 'dots-vertical', 'envelope': 'mail', 'eraser': 'eraser',
    'exclamation': 'exclamation-mark', 'expand': 'arrows-maximize', 'eye-dropper': 'color-picker',
    'eye-slash': 'eye-off', 'face-smile': 'mood-smile', 'file': 'file', 'file-circle-minus': 'file-minus',
    'file-circle-plus': 'file-plus', 'file-invoice': 'file-invoice', 'file-pen': 'file-pencil',
    'file-text': 'file-text', 'file-video': 'movie', 'film': 'movie', 'filter': 'filter',
    'floppy-disk': 'device-floppy', 'folder': 'folder', 'folder-open': 'folder-open', 'folder-plus': 'folder-plus',
    'folder-tree': 'folders', 'font': 'typography', 'futbol': 'ball-football', 'gauge': 'gauge', 'gear': 'settings',
    'globe': 'world', 'graduation-cap': 'school', 'hammer': 'hammer', 'hard-drive': 'server',
    'highlighter': 'highlight', 'history': 'history', 'hourglass': 'hourglass', 'house': 'home', 'id-badge': 'id-badge',
    'image': 'photo', 'indent': 'indent-increase', 'info': 'info-small', 'italic': 'italic', 'language': 'language',
    'laptop-file': 'device-laptop', 'layer-group': 'stack-2', 'leaf': 'leaf', 'life-ring': 'lifebuoy',
    'lightbulb': 'bulb', 'link': 'link', 'link-slash': 'link-off', 'list': 'list', 'list-check': 'list-check',
    'list-ol': 'list-numbers', 'list-ul': 'list', 'location-dot': 'map-pin', 'lock': 'lock', 'lock-open': 'lock-open',
    'low-vision': 'eye-exclamation', 'magnifying-glass': 'search', 'magnifying-glass-plus': 'zoom-in',
    'maximize': 'maximize', 'message': 'message', 'microphone-slash': 'microphone-off', 'minus': 'minus',
    'network-wired': 'network', 'newspaper': 'news', 'outdent': 'indent-decrease', 'paper-plane': 'send',
    'paste': 'clipboard', 'peace': 'peace', 'pen': 'pencil', 'pen-clip': 'pencil', 'pen-to-square': 'edit',
    'photo-film': 'photo-video', 'pizza-slice': 'pizza', 'plane': 'plane', 'play': 'player-play', 'plus': 'plus',
    'plus-square-o': 'square-plus', 'print': 'printer', 'question': 'question-mark', 'question-circle': 'help-circle',
    'quote-left': 'quote', 'quote-right': 'quote', 'rectangle-list': 'list-details', 'right-left': 'arrows-exchange',
    'rotate-left': 'rotate', 'rotate-right': 'rotate-clockwise', 'rss': 'rss', 'ruler-horizontal': 'ruler-2',
    'scale-balanced': 'scale', 'scissors': 'scissors', 'shapes': 'triangle-square-circle', 'share': 'share-3',
    'share-from-square': 'share-2', 'sort': 'arrows-sort', 'spell-check': 'text-spellcheck', 'spinner': 'loader-2',
    'square-arrow-up-right': 'external-link', 'square-plus': 'square-plus', 'square-root-variable': 'square-root',
    'stop': 'player-stop', 'strikethrough': 'strikethrough', 'subscript': 'subscript',
    'suitcase-rolling': 'luggage', 'superscript': 'superscript', 'table': 'table', 'table-list': 'list-details',
    'tags': 'tags', 'thumbs-up': 'thumb-up', 'trash-can': 'trash', 'triangle-exclamation': 'alert-triangle',
    'turn-down': 'corner-left-down', 'turn-up': 'corner-left-up', 'underline': 'underline',
    'universal-access': 'accessible', 'unlock': 'lock-open', 'unlock-keyhole': 'lock-open',
    'up-right-and-down-left-from-center': 'arrows-maximize', 'upload': 'upload', 'user': 'user',
    'user-check': 'user-check', 'user-group': 'users', 'user-plus': 'user-plus', 'user-xmark': 'user-x',
    'users': 'users', 'users-line': 'users-group', 'volume-high': 'volume', 'wand-magic-sparkles': 'wand',
    'warning': 'alert-triangle', 'wrench': 'tool', 'xmark': 'x',
}

# Icones que fan servir directament les plantilles del tema.
THEME_ICONS = '''id-badge-2 world arrow-right school books award calendar-event clock device-laptop users map-pin
    file-certificate layout-dashboard building-hospital certificate search shopping-cart mail phone'''.split()


def main():
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    package = sys.argv[1]
    css = open(os.path.join(package, 'dist', 'tabler-icons.css')).read()
    codes = dict(re.findall(r'\.ti-([a-z0-9-]+):before\s*\{\s*content:\s*"([^"]+)"', css))

    missing = sorted({ti for ti in list(MAP.values()) + THEME_ICONS if ti not in codes})
    if missing:
        sys.exit('No existeixen a Tabler Icons: ' + ', '.join(missing))

    version = re.search(r'"version":\s*"([^"]+)"', open(os.path.join(package, 'package.json')).read()).group(1)
    os.makedirs(os.path.join(THEME, 'fonts'), exist_ok=True)
    shutil.copy(os.path.join(package, 'dist', 'fonts', 'tabler-icons.woff2'), os.path.join(THEME, 'fonts', 'tabler-icons.woff2'))

    used = sorted(set(MAP.values()) | set(THEME_ICONS))
    scss = [
        '// Tabler Icons %s (MIT). Generat per scripts/theme-icons.py: no l\'editis a mà.' % version,
        '@font-face {',
        '    font-family: "tabler-icons";',
        '    font-style: normal;',
        '    font-weight: 400;',
        '    font-display: block;',
        '    src: url("[[font:theme|tabler-icons.woff2]]") format("woff2");',
        '}',
        '',
    ]
    scss += ['.ti-%s::before { content: "%s"; }' % (name, codes[name]) for name in used]
    os.makedirs(os.path.join(THEME, 'scss', 'pspv'), exist_ok=True)
    open(os.path.join(THEME, 'scss', 'pspv', '_icons.scss'), 'w').write('\n'.join(scss) + '\n')

    header = open(os.path.join(ROOT, 'plugins', 'local', 'pspvcore', 'version.php')).read().split('/**')[0]
    php = header + '''/**
 * Font Awesome to Tabler Icons map. Generated by scripts/theme-icons.py: do not edit by hand.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

return [
''' + ''.join("    '%s' => '%s',\n" % (fa, MAP[fa]) for fa in sorted(MAP)) + '];\n'
    os.makedirs(os.path.join(THEME, 'classes', 'output'), exist_ok=True)
    open(os.path.join(THEME, 'classes', 'output', 'tabler_map.php'), 'w').write(php)
    print('%d icones de Font Awesome mapades, %d classes de Tabler, Tabler Icons %s' % (len(MAP), len(used), version))


if __name__ == '__main__':
    main()
