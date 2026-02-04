@props([
    'name',
    'class' => 'w-5 h-5',
    'strokeWidth' => '1.5',
])

@php
    $icons = [
        'home' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M8.25 21h8.25', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'cog-6-tooth' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'clipboard-document-list' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M9 12h3.75M9 15h3.75M9 18h3.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M18 18.75h.75A2.25 2.25 0 0 0 21 16.5V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M8.25 8.25H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15 3.75h-1.5A2.25 2.25 0 0 0 11.25 6v0c0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75v0A2.25 2.25 0 0 0 15 3.75Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'archive-box' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M10 11.25h4', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'lifebuoy' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M16.712 4.33a9.027 9.027 0 0 1 1.652 1.306c.51.51.944 1.064 1.306 1.652', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M16.712 4.33 13.264 8.468', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M16.712 4.33a9.014 9.014 0 0 0-9.424 0', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M19.67 7.288 15.532 10.736', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M19.67 7.288a9.014 9.014 0 0 1 0 9.424', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15.532 10.736a3.736 3.736 0 0 0-.88-1.388 3.737 3.737 0 0 0-1.388-.88', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15.532 10.736a3.765 3.765 0 0 1 0 2.528', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M13.264 8.468a3.765 3.765 0 0 0-2.528 0', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15.532 13.264c-.181.506-.475.982-.88 1.388a3.736 3.736 0 0 1-1.388.88', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M13.264 12 17.402 15.448', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M17.402 15.448a9.027 9.027 0 0 1-1.306 1.652 9.027 9.027 0 0 1-1.652 1.306', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15.448 19.67 12 15.532', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15.448 19.67a9.014 9.014 0 0 1-9.424 0', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M12 15.532a3.765 3.765 0 0 1-2.528 0 3.736 3.736 0 0 1-1.388-.88 3.737 3.737 0 0 1-.88-1.388', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M10.732 12 6.594 15.448', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M6.594 15.448a9.024 9.024 0 0 1-1.652-1.306 9.027 9.027 0 0 1-1.306-1.652', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M3.636 12a9.014 9.014 0 0 1 0-9.424', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M6.594 8.552 10.732 12', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M6.594 8.552a3.765 3.765 0 0 1 0 2.528', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M6.594 8.552c.181-.506.475-.982.88-1.388a3.736 3.736 0 0 1 1.388-.88', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M6.594 8.552 4.33 7.288', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M7.288 4.33a9.024 9.024 0 0 0-1.652 1.306A9.025 9.025 0 0 0 4.33 7.288', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'user-circle' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M17.982 18.725A9 9 0 1 0 6.018 18.725', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M12 21a8.966 8.966 0 0 1-5.982-2.275', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'pencil-square' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M16.862 4.487 19.5 7.125', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'arrow-right-on-rectangle' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'm18.75 15 3-3m0 0-3-3m3 3H9', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'information-circle' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M12 8.25h.008v.008H12V8.25Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'sparkles' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'book-open' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M12 6.042A8.966 8.966 0 0 1 18 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M12 6.042v14.25', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'envelope' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15A2.25 2.25 0 0 1 2.25 17.25V6.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M21.75 6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M21.75 6.993a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91A2.25 2.25 0 0 1 2.25 6.993', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'clock' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M12 6v6h4.5', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'question-mark-circle' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M12 17.25h.008v.008H12v-.008Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'chat-bubble-left-right' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M2.25 12.75c0-1.593.433-3.087 1.182-4.374a.75.75 0 0 1 .652-.376h4.01c.285 0 .55.16.676.414l.995 1.989a.75.75 0 0 0 .676.414h2.869a.75.75 0 0 1 .75.75v1.773c0 .414-.336.75-.75.75h-1.478a.375.375 0 0 0-.27.11l-2.399 2.399a.375.375 0 0 1-.63-.265v-.551a.375.375 0 0 0-.375-.375H4.084a.75.75 0 0 1-.652-.376 8.977 8.977 0 0 1-1.182-4.374Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M21.75 11.25c0 1.593-.433 3.087-1.182 4.374a.75.75 0 0 1-.652.376h-4.01a.75.75 0 0 1-.676-.414l-.995-1.989a.75.75 0 0 0-.676-.414h-2.869a.75.75 0 0 1-.75-.75V10.05c0-.414.336-.75.75-.75h1.478a.375.375 0 0 0 .27-.11l2.399-2.399a.375.375 0 0 1 .63.265v.551c0 .207.168.375.375.375h2.861a.75.75 0 0 1 .652.376 8.977 8.977 0 0 1 1.182 4.374Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'calendar' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M6.75 3v2.25M17.25 3v2.25M3 9h18', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M19.5 5.25h-15A1.5 1.5 0 0 0 3 6.75v12A1.5 1.5 0 0 0 4.5 20.25h15a1.5 1.5 0 0 0 1.5-1.5v-12a1.5 1.5 0 0 0-1.5-1.5Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'check-badge' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M9 12.75 11.25 15 15 9.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'shield-check' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M9 12.75 11.25 15 15 9.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M12 2.25c-.621 0-1.233.103-1.81.3a8.994 8.994 0 0 0-5.717 5.41c-.2.568-.316 1.17-.316 1.79 0 5.591 3.807 10.29 9 11.621 5.193-1.33 9-6.03 9-11.621 0-.62-.115-1.222-.316-1.79a8.994 8.994 0 0 0-5.717-5.41A5.985 5.985 0 0 0 12 2.25Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'squares-2x2' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25A2.25 2.25 0 0 1 8.25 10.5H6a2.25 2.25 0 0 1-2.25-2.25V6Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-2.25Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
                ['d' => 'M13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'chevron-right' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'm8.25 4.5 7.5 7.5-7.5 7.5', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
        'arrow-up' => [
            'viewBox' => '0 0 24 24',
            'paths' => [
                ['d' => 'M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'],
            ],
        ],
    ];

    $icon = $icons[$name] ?? null;
@endphp

@if($icon)
    <svg {{ $attributes->merge(['class' => $class]) }} fill="none" viewBox="{{ $icon['viewBox'] }}" stroke-width="{{ $strokeWidth }}" stroke="currentColor" aria-hidden="true">
        @foreach($icon['paths'] as $path)
            <path stroke-linecap="{{ $path['stroke-linecap'] ?? 'round' }}" stroke-linejoin="{{ $path['stroke-linejoin'] ?? 'round' }}" d="{{ $path['d'] }}" />
        @endforeach
    </svg>
@endif
