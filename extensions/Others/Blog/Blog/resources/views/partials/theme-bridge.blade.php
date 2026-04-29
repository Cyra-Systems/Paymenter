@once
    <style>
        .blog-ui {
            --blog-primary-token: var(--color-primary, 221 83% 53%);
            --blog-neutral-token: var(--color-neutral, 215 16% 47%);
            --blog-base-token: var(--color-base, 222 47% 11%);
            --blog-inverted-token: var(--color-inverted, 0 0% 100%);
            --blog-background-token: var(--color-background, 0 0% 100%);
            --blog-surface-token: var(--color-background-secondary, 210 20% 98%);
            --blog-primary: var(--theme-primary, hsl(var(--blog-primary-token)));
            --blog-neutral: var(--theme-neutral, hsl(var(--blog-neutral-token)));
            --blog-base: var(--theme-base, hsl(var(--blog-base-token)));
            --blog-inverted: var(--theme-inverted, hsl(var(--blog-inverted-token)));
            --blog-background: var(--theme-background, hsl(var(--blog-background-token)));
            --blog-surface: var(--theme-background-secondary, hsl(var(--blog-surface-token)));
            color: var(--blog-base);
        }

        .blog-ui .text-base {
            color: var(--blog-base) !important;
            font-size: inherit !important;
            line-height: inherit !important;
        }

        .blog-ui .text-inverted {
            color: var(--blog-inverted) !important;
        }

        .blog-ui .text-primary {
            color: var(--blog-primary) !important;
        }

        .blog-ui .text-color-base {
            color: var(--blog-base) !important;
        }

        .blog-ui .text-color-muted,
        .blog-ui .text-neutral-500,
        .blog-ui .text-neutral-400,
        .blog-ui .text-neutral-300 {
            color: color-mix(in srgb, var(--blog-base) 60%, transparent) !important;
        }

        .blog-ui .text-base\/85,
        .blog-ui .text-base\/82,
        .blog-ui .text-base\/80,
        .blog-ui .text-base\/75,
        .blog-ui .text-base\/70,
        .blog-ui .text-base\/68,
        .blog-ui .text-base\/65,
        .blog-ui .text-base\/60,
        .blog-ui .text-base\/55,
        .blog-ui .text-base\/50,
        .blog-ui .text-base\/45,
        .blog-ui .text-base\/35,
        .blog-ui .text-base\/30 {
            font-size: inherit !important;
            line-height: inherit !important;
        }

        .blog-ui .text-base\/85 { color: color-mix(in srgb, var(--blog-base) 85%, transparent) !important; }
        .blog-ui .text-base\/82 { color: color-mix(in srgb, var(--blog-base) 82%, transparent) !important; }
        .blog-ui .text-base\/80 { color: color-mix(in srgb, var(--blog-base) 80%, transparent) !important; }
        .blog-ui .text-base\/75 { color: color-mix(in srgb, var(--blog-base) 75%, transparent) !important; }
        .blog-ui .text-base\/70 { color: color-mix(in srgb, var(--blog-base) 70%, transparent) !important; }
        .blog-ui .text-base\/68 { color: color-mix(in srgb, var(--blog-base) 68%, transparent) !important; }
        .blog-ui .text-base\/65 { color: color-mix(in srgb, var(--blog-base) 65%, transparent) !important; }
        .blog-ui .text-base\/60 { color: color-mix(in srgb, var(--blog-base) 60%, transparent) !important; }
        .blog-ui .text-base\/55 { color: color-mix(in srgb, var(--blog-base) 55%, transparent) !important; }
        .blog-ui .text-base\/50 { color: color-mix(in srgb, var(--blog-base) 50%, transparent) !important; }
        .blog-ui .text-base\/45 { color: color-mix(in srgb, var(--blog-base) 45%, transparent) !important; }
        .blog-ui .text-base\/35 { color: color-mix(in srgb, var(--blog-base) 35%, transparent) !important; }
        .blog-ui .text-base\/30 { color: color-mix(in srgb, var(--blog-base) 30%, transparent) !important; }

        .blog-ui .bg-background {
            background-color: var(--blog-background) !important;
        }

        .blog-ui .bg-background\/60 {
            background-color: color-mix(in srgb, var(--blog-background) 60%, transparent) !important;
        }

        .blog-ui .bg-background-secondary,
        .blog-ui .bg-background-secondary\/95,
        .blog-ui .bg-background-secondary\/85 {
            background-color: var(--blog-surface) !important;
        }

        .blog-ui .bg-primary {
            background-color: var(--blog-primary) !important;
        }

        .blog-ui .border-neutral,
        .blog-ui .border-neutral\/70,
        .blog-ui .border-neutral\/60,
        .blog-ui .border-neutral\/50,
        .blog-ui .border-neutral\/40 {
            border-color: color-mix(in srgb, var(--blog-neutral) 72%, transparent) !important;
        }

        .blog-ui .border-primary\/45,
        .blog-ui .border-primary\/30 {
            border-color: color-mix(in srgb, var(--blog-primary) 35%, transparent) !important;
        }

        .blog-ui .hover\:text-base:hover {
            color: var(--blog-base) !important;
        }

        .blog-ui .hover\:bg-background:hover {
            background-color: var(--blog-background) !important;
        }

        .blog-ui .hover\:bg-background-secondary:hover {
            background-color: color-mix(in srgb, var(--blog-surface) 94%, transparent) !important;
        }

        .blog-ui .hover\:bg-primary\/80:hover {
            background-color: color-mix(in srgb, var(--blog-primary) 84%, black 8%) !important;
        }

        .blog-archive-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .blog-archive-controls {
            display: flex;
            width: 100%;
            flex-direction: column;
            gap: 0.75rem;
        }

        .blog-archive-search,
        .blog-archive-sort-wrap {
            width: 100%;
        }

        .blog-archive-search {
            padding-left: 2.25rem !important;
            padding-right: 0.75rem !important;
        }

        .blog-archive-input {
            height: 2.5rem;
            width: 100%;
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 62%, transparent);
            border-radius: 0.125rem;
            background: var(--blog-surface);
            color: var(--blog-base);
            font-size: 0.875rem;
            padding-inline: 0.75rem;
            transition: border-color 140ms ease, background-color 140ms ease;
        }

        .blog-archive-input::placeholder {
            color: color-mix(in srgb, var(--blog-base) 46%, transparent);
        }

        .blog-archive-input:focus {
            outline: none;
            border-color: color-mix(in srgb, var(--blog-primary) 60%, transparent);
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--blog-primary) 35%, transparent);
        }

        .blog-archive-select {
            cursor: pointer;
            padding-left: 0.75rem !important;
            padding-right: 2.5rem !important;
        }

        .blog-archive-topic-chip,
        .blog-archive-clear-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 62%, transparent);
            border-radius: 0.125rem;
            background: var(--blog-surface);
            padding: 0.375rem 0.625rem;
            font-size: 0.75rem;
            color: color-mix(in srgb, var(--blog-base) 76%, transparent);
            transition: border-color 140ms ease, color 140ms ease, background-color 140ms ease;
        }

        .blog-archive-topic-chip:hover,
        .blog-archive-clear-chip:hover {
            border-color: color-mix(in srgb, var(--blog-primary) 35%, transparent);
            color: var(--blog-base);
        }

        .blog-archive-topic-chip--active {
            border-color: color-mix(in srgb, var(--blog-primary) 45%, transparent);
            color: var(--blog-primary);
            background: color-mix(in srgb, var(--blog-primary) 8%, var(--blog-surface));
        }

        .blog-archive-clear-chip {
            margin-left: auto;
            color: #b91c1c;
        }

        .blog-post-card {
            position: relative;
            border-radius: 0.125rem;
            background: var(--blog-surface);
            transition: border-color 140ms ease, background-color 140ms ease, transform 140ms ease;
        }

        .blog-post-card-media {
            position: relative;
            aspect-ratio: 16 / 9;
            max-height: 14rem;
            overflow: hidden;
            border-bottom: 1px solid color-mix(in srgb, var(--blog-neutral) 56%, transparent);
            background: color-mix(in srgb, var(--blog-background) 92%, var(--blog-surface));
        }

        .blog-post-card-image {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 180ms ease;
        }

        .blog-post-card-placeholder {
            display: flex;
            height: 100%;
            width: 100%;
            align-items: center;
            justify-content: center;
            color: color-mix(in srgb, var(--blog-base) 28%, transparent);
        }

        .blog-post-card-arrow {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 1.75rem;
            width: 1.75rem;
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 62%, transparent);
            background: color-mix(in srgb, var(--blog-surface) 96%, transparent);
            color: color-mix(in srgb, var(--blog-base) 70%, transparent);
            transition: transform 140ms ease, border-color 140ms ease, color 140ms ease;
        }

        .blog-post-card-badge,
        .blog-post-card-tag {
            display: inline-flex;
            align-items: center;
            min-height: 1.5rem;
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 58%, transparent);
            border-radius: 0.125rem;
            padding: 0.125rem 0.5rem;
            font-size: 0.6875rem;
            line-height: 1.1rem;
        }

        .blog-post-card-badge {
            border-color: color-mix(in srgb, var(--blog-primary) 35%, transparent);
            color: var(--blog-primary);
            background: color-mix(in srgb, var(--blog-primary) 8%, var(--blog-surface));
        }

        .blog-post-card-tag {
            color: color-mix(in srgb, var(--blog-base) 72%, transparent);
            background: color-mix(in srgb, var(--blog-background) 88%, var(--blog-surface));
        }

        .blog-post-card-title {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.4;
            letter-spacing: -0.01em;
            color: var(--blog-base);
        }

        .blog-post-card-description {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            font-size: 0.875rem;
            line-height: 1.55;
            color: color-mix(in srgb, var(--blog-base) 72%, transparent);
        }

        .blog-post-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            border-top: 1px solid color-mix(in srgb, var(--blog-neutral) 56%, transparent);
            padding-top: 0.75rem;
        }

        .blog-post-card-read {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--blog-primary);
            font-size: 0.75rem;
            font-weight: 600;
            transition: transform 140ms ease;
        }

        .blog-post-card::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 140ms ease;
        }

        .blog-post-card--glow::after {
            background: linear-gradient(
                180deg,
                color-mix(in srgb, var(--blog-primary) 4%, transparent) 0%,
                transparent 35%,
                color-mix(in srgb, var(--blog-primary) 8%, transparent) 100%
            );
        }

        .blog-post-card--hover:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--blog-primary) 35%, transparent) !important;
        }

        .blog-post-card--hover:hover .blog-post-card-image {
            transform: scale(1.03);
        }

        .blog-post-card--hover:hover .blog-post-card-arrow {
            transform: translate(1px, -1px);
            border-color: color-mix(in srgb, var(--blog-primary) 35%, transparent);
            color: var(--blog-primary);
        }

        .blog-post-card--hover:hover .blog-post-card-read {
            transform: translateX(2px);
        }

        .blog-post-card--glow:hover::after {
            opacity: 1;
        }

        .blog-archive-page-button {
            display: inline-flex;
            min-height: 2.5rem;
            min-width: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.125rem;
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 62%, transparent);
            background: var(--blog-surface);
            color: color-mix(in srgb, var(--blog-base) 74%, transparent);
            transition: border-color 140ms ease, background-color 140ms ease, color 140ms ease;
        }

        .blog-archive-page-button:hover:not(:disabled) {
            border-color: color-mix(in srgb, var(--blog-primary) 35%, transparent);
            color: var(--blog-base);
        }

        .blog-archive-page-button:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        .blog-archive-page-button--active {
            border-color: color-mix(in srgb, var(--blog-primary) 45%, transparent);
            background: color-mix(in srgb, var(--blog-primary) 10%, var(--blog-surface));
            color: var(--blog-primary);
        }

        .blog-archive-page-button--ghost {
            background: var(--blog-background);
        }

        .blog-archive-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            border: 1px dashed color-mix(in srgb, var(--blog-neutral) 58%, transparent);
            border-radius: 0.125rem;
            background: var(--blog-surface);
            padding: 2rem 1rem;
            text-align: center;
        }

        .blog-archive-empty-action {
            color: var(--blog-primary);
            font-size: 0.875rem;
            font-weight: 600;
        }

        @media (max-width: 640px) {
            .blog-archive-clear-chip {
                margin-left: 0;
            }
        }

        @media (min-width: 640px) {
            .blog-archive-controls {
                flex-direction: row;
                align-items: center;
            }

            .blog-archive-search {
                min-width: 16rem;
            }

            .blog-archive-sort-wrap {
                width: 9rem;
                flex: 0 0 9rem;
            }
        }

        @media (min-width: 1024px) {
            .blog-archive-header {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: start;
                gap: 1.5rem;
            }

            .blog-archive-controls {
                width: auto;
                min-width: 26rem;
                justify-content: flex-end;
            }
        }
    </style>
@endonce
