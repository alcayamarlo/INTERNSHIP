@extends('layouts.app')

@section('title', 'My competencies')

@push('styles')
<style>
    #competenciesOverview {
        --co-page: var(--sb-canvas);
        --co-panel: var(--sb-surface);
        --co-card: var(--sb-surface);
        --co-raised: var(--sb-surface-raised);
        --co-border: var(--sb-border);
        --co-border-strong: var(--sb-control-border);
        --co-accent: var(--sb-brand);
        --co-accent-text: var(--sb-brand-content);
        --co-accent-hover: var(--sb-brand-deep);
        --co-text-on-accent: #fff;
        --co-accent-soft: var(--sb-red-soft);
        --co-accent-ring: var(--sb-brand-ring);
        --co-text: var(--sb-ink);
        --co-secondary: var(--sb-text-soft);
        --co-muted: var(--sb-muted);
        --co-verified: var(--sb-success-ink);
        --co-verified-bg: var(--sb-success-bg);
        --co-pending: var(--sb-warning-ink);
        --co-pending-bg: var(--sb-warning-bg);
        --co-rejected: var(--sb-danger-ink);
        --co-rejected-bg: var(--sb-danger-bg);
        --co-neutral: var(--sb-text-soft);
        --co-neutral-bg: var(--sb-surface-raised);
        --co-radius-control: 8px;
        --co-radius-card: 14px;
        --co-radius-panel: 18px;
        --co-space-1: 4px;
        --co-space-2: 8px;
        --co-space-3: 12px;
        --co-space-4: 16px;
        --co-space-5: 24px;
        --co-space-6: 32px;
        --co-dur-fast: 120ms;
        --co-dur-base: 200ms;
        --co-dur-press: 100ms;
        --co-dur-selection: 150ms;
        --co-dur-crossfade: 150ms;
        --co-dur-list-out: 100ms;
        --co-dur-detail: 180ms;
        --co-dur-toggle: 250ms;
        --co-dur-grid: 250ms;
        --co-dur-pulse: 400ms;
        --co-delay-row: 30ms;
        --co-delay-level: 40ms;
        --co-dur-slow: 450ms;
        --co-dur-hero: 800ms;
        --co-ease-out: cubic-bezier(.16, 1, .3, 1);
        --co-ease-in-out: cubic-bezier(.65, 0, .35, 1);
        color: var(--co-text);
        padding: var(--co-space-2) 0 var(--co-space-6);
    }

    #competenciesOverview *,
    #competenciesOverview *::before,
    #competenciesOverview *::after {
        box-sizing: border-box;
    }

    #competenciesOverview h1,
    #competenciesOverview h2,
    #competenciesOverview h3,
    #competenciesOverview p {
        margin: 0;
    }

    #competenciesOverview .co-number {
        font-variant-numeric: lining-nums tabular-nums;
    }

    #competenciesOverview .co-header {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: var(--co-space-4);
        justify-content: space-between;
        margin-bottom: var(--co-space-5);
    }

    #competenciesOverview .co-title {
        color: var(--co-text);
        font-size: clamp(24px, 2.4vw, 30px);
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -.035em;
    }

    #competenciesOverview .co-hint {
        color: var(--co-muted);
        font-size: 15px;
        margin-top: 6px;
    }

    #competenciesOverview .co-segment {
        background: var(--co-panel);
        border: 1px solid var(--co-border-strong);
        border-radius: var(--co-radius-control);
        display: grid;
        grid-template-columns: 1fr 1fr;
        isolation: isolate;
        padding: var(--co-space-1);
        position: relative;
    }

    #competenciesOverview .co-segment-pill {
        background: var(--co-accent);
        border-radius: var(--co-radius-control);
        inset: var(--co-space-1) auto var(--co-space-1) var(--co-space-1);
        pointer-events: none;
        position: absolute;
        transform: translateX(0);
        transition: transform var(--co-dur-toggle) var(--co-ease-out);
        width: calc(50% - var(--co-space-1));
        z-index: -1;
    }

    #competenciesOverview .co-segment[data-view="all"] .co-segment-pill {
        transform: translateX(100%);
    }

    #competenciesOverview .co-segment-button,
    #competenciesOverview .co-chip,
    #competenciesOverview .co-axis-button,
    #competenciesOverview .co-row,
    #competenciesOverview .co-primary,
    #competenciesOverview .co-empty-action {
        appearance: none;
        border: 1px solid transparent;
        color: var(--co-secondary);
        cursor: pointer;
        font: inherit;
        font-weight: 400;
        transition:
            background-color var(--co-dur-fast) var(--co-ease-out),
            border-color var(--co-dur-fast) var(--co-ease-out),
            color var(--co-dur-fast) var(--co-ease-out),
            opacity var(--co-dur-fast) var(--co-ease-out),
            transform var(--co-dur-base) var(--co-ease-out),
            font-weight var(--co-dur-selection) var(--co-ease-out);
    }

    #competenciesOverview .co-segment-button {
        background: transparent;
        border-radius: var(--co-radius-control);
        min-height: 44px;
        min-width: 136px;
        padding: var(--co-space-2) var(--co-space-3);
        position: relative;
        z-index: 1;
    }

    #competenciesOverview .co-segment-button:hover,
    #competenciesOverview .co-chip:hover,
    #competenciesOverview .co-axis-button:hover,
    #competenciesOverview .co-row:hover {
        background-color: var(--co-raised);
        color: var(--co-text);
    }

    #competenciesOverview .co-segment-button[aria-pressed="true"] {
        color: var(--co-text-on-accent);
        font-weight: 500;
    }

    #competenciesOverview .co-segment-button:focus-visible,
    #competenciesOverview .co-segment-button:hover {
        background-color: transparent;
        border-color: transparent;
    }

    #competenciesOverview .co-segment-button:active,
    #competenciesOverview .co-chip:active,
    #competenciesOverview .co-axis-button:active,
    #competenciesOverview .co-row:active,
    #competenciesOverview .co-primary:active,
    #competenciesOverview .co-empty-action:active {
        transform: scale(.98);
        transition-duration: var(--co-dur-press);
    }

    #competenciesOverview button:focus-visible,
    #competenciesOverview a:focus-visible {
        outline: 2px solid var(--co-accent);
        outline-offset: 2px;
    }

    #competenciesOverview button:disabled,
    #competenciesOverview a[aria-disabled="true"] {
        background-color: var(--co-panel);
        border-color: var(--co-border);
        color: var(--co-muted);
        cursor: not-allowed;
        opacity: .55;
        transform: none;
    }

    #competenciesOverview .co-layout {
        align-items: start;
        display: grid;
        gap: var(--co-space-5);
        grid-template-columns: minmax(300px, .92fr) minmax(380px, 1.08fr);
    }

    #competenciesOverview .co-card {
        background: var(--co-card);
        border: 1px solid var(--co-border);
        border-radius: var(--co-radius-card);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04), 0 8px 24px rgba(0, 0, 0, .25);
        min-width: 0;
        padding: var(--co-space-5);
    }

    #competenciesOverview .co-outer-panel {
        background: var(--co-panel);
        border: 1px solid var(--co-border);
        border-radius: var(--co-radius-panel);
        padding: var(--co-space-5);
    }

    #competenciesOverview .co-card-title {
        color: var(--co-text);
        font-size: 16px;
        font-weight: 500;
        letter-spacing: -.01em;
        margin-bottom: var(--co-space-4);
    }

    #competenciesOverview .co-radar-wrap {
        margin: 0 auto;
        max-width: 440px;
        position: relative;
        width: 100%;
    }

    #competenciesOverview .co-radar {
        display: block;
        height: auto;
        overflow: visible;
        width: 100%;
    }

    #competenciesOverview .co-grid-layer,
    #competenciesOverview .co-axis-layer {
        opacity: 0;
        transition: opacity var(--co-dur-grid) var(--co-ease-out);
    }

    #competenciesOverview .co-radar.is-ready .co-grid-layer,
    #competenciesOverview .co-radar.is-ready .co-axis-layer {
        opacity: 1;
    }

    #competenciesOverview .co-ring {
        fill: none;
        stroke: rgba(142, 163, 187, .18);
        stroke-width: 1;
    }

    #competenciesOverview .co-ring-outer {
        stroke: rgba(142, 163, 187, .36);
    }

    #competenciesOverview .co-axis-line {
        stroke: rgba(142, 163, 187, .18);
        stroke-width: 1;
    }

    #competenciesOverview .co-primary-shape {
        fill: url(#competencyRadarFill);
        stroke: var(--co-accent);
        stroke-linejoin: round;
        stroke-width: 1.5;
    }

    #competenciesOverview .co-secondary-shape {
        fill: none;
        stroke: rgba(169, 186, 208, .68);
        stroke-dasharray: 4 4;
        stroke-linejoin: round;
        stroke-width: 1.5;
    }

    #competenciesOverview .co-vertex-halo {
        fill: rgba(56, 189, 248, .15);
        opacity: 0;
        r: 7px;
        transition: opacity var(--co-dur-fast) var(--co-ease-out);
    }

    #competenciesOverview .co-vertex-halo.is-selected {
        opacity: 1;
    }

    #competenciesOverview .co-vertex {
        fill: var(--co-accent);
        r: 4px;
        stroke: var(--co-card);
        stroke-width: 2;
        transform-box: fill-box;
        transform-origin: center;
    }

    #competenciesOverview .co-vertex-hit {
        fill: transparent;
        pointer-events: all;
        r: 4px;
        stroke: transparent;
        stroke-width: 1px;
    }

    #competenciesOverview .co-vertex-hit.is-inactive {
        pointer-events: none;
    }

    #competenciesOverview .co-vertex-hit:focus-visible {
        fill: var(--co-accent-ring);
        outline: 2px solid var(--co-accent);
        outline-offset: 2px;
    }

    #competenciesOverview .co-vertex-tooltip {
        background: var(--co-panel);
        border: 1px solid var(--co-border-strong);
        border-radius: var(--co-radius-control);
        color: var(--co-text);
        font-size: 12px;
        left: 0;
        opacity: 0;
        padding: var(--co-space-2) var(--co-space-3);
        pointer-events: none;
        position: absolute;
        top: 0;
        transform: translate(-50%, -100%);
        transition: opacity var(--co-dur-fast) var(--co-ease-out);
        white-space: nowrap;
        z-index: 6;
    }

    #competenciesOverview .co-vertex-tooltip.is-visible {
        opacity: 1;
    }

    #competenciesOverview .co-axis-button {
        align-items: center;
        background: transparent;
        border-radius: var(--co-radius-control);
        display: flex;
        flex-direction: column;
        height: 52px;
        justify-content: center;
        min-height: 44px;
        padding: var(--co-space-1);
        position: absolute;
        text-align: center;
        width: 112px;
        z-index: 2;
    }

    #competenciesOverview .co-axis-button[data-axis="technical"] {
        left: 50%;
        top: 0;
        transform: translateX(-50%);
    }

    #competenciesOverview .co-axis-button[data-axis="soft"] {
        left: 1%;
        top: 62%;
    }

    #competenciesOverview .co-axis-button[data-axis="certification"] {
        right: 1%;
        top: 62%;
    }

    #competenciesOverview .co-axis-button:hover,
    #competenciesOverview .co-axis-button:active {
        transform: none;
    }

    #competenciesOverview .co-axis-button[data-axis="technical"]:active {
        transform: translateX(-50%) scale(.98);
    }

    #competenciesOverview .co-axis-name {
        color: var(--co-secondary);
        font-size: 12px;
        font-weight: 400;
        line-height: 16px;
        transition: color var(--co-dur-selection) var(--co-ease-out), font-weight var(--co-dur-selection) var(--co-ease-out);
    }

    #competenciesOverview .co-axis-value {
        color: var(--co-text);
        font-size: 14px;
        font-variant-numeric: lining-nums tabular-nums;
        font-weight: 500;
        line-height: 18px;
    }

    #competenciesOverview .co-axis-button[aria-pressed="true"] .co-axis-name {
        color: var(--co-accent);
        font-weight: 500;
    }

    #competenciesOverview .co-tooltip {
        background: var(--co-panel);
        border: 1px solid var(--co-border-strong);
        border-radius: var(--co-radius-control);
        color: var(--co-text);
        font-size: 12px;
        left: 50%;
        opacity: 0;
        padding: var(--co-space-2) var(--co-space-3);
        pointer-events: none;
        position: absolute;
        top: calc(100% + var(--co-space-1));
        transform: translate(-50%, -2px);
        transition: opacity var(--co-dur-fast) var(--co-ease-out), transform var(--co-dur-fast) var(--co-ease-out);
        white-space: nowrap;
        z-index: 5;
    }

    #competenciesOverview .co-axis-button:hover .co-tooltip,
    #competenciesOverview .co-axis-button:focus-visible .co-tooltip {
        opacity: 1;
        transform: translate(-50%, 0);
    }

    #competenciesOverview .co-axis-button[data-axis="soft"] .co-tooltip {
        left: 0;
        transform: translate(0, calc(0px - var(--co-space-1)));
    }

    #competenciesOverview .co-axis-button[data-axis="certification"] .co-tooltip {
        left: auto;
        right: 0;
        transform: translate(0, calc(0px - var(--co-space-1)));
    }

    #competenciesOverview .co-axis-button[data-axis="soft"]:hover .co-tooltip,
    #competenciesOverview .co-axis-button[data-axis="soft"]:focus-visible .co-tooltip,
    #competenciesOverview .co-axis-button[data-axis="certification"]:hover .co-tooltip,
    #competenciesOverview .co-axis-button[data-axis="certification"]:focus-visible .co-tooltip {
        transform: translate(0, 0);
    }

    #competenciesOverview .co-axis-button:focus-visible {
        background-color: var(--co-panel);
        border-color: var(--co-accent);
        color: var(--co-text);
    }

    #competenciesOverview .co-strength {
        border-top: 1px solid var(--co-border);
        margin-top: var(--co-space-4);
        padding-top: var(--co-space-5);
    }

    #competenciesOverview .co-overline {
        color: var(--co-muted);
        font-size: 12px;
        font-weight: 500;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    #competenciesOverview .co-strength-main {
        align-items: end;
        display: flex;
        flex-wrap: wrap;
        gap: var(--co-space-2) var(--co-space-4);
        justify-content: space-between;
        margin-top: var(--co-space-2);
    }

    #competenciesOverview .co-strength-level {
        color: var(--co-text);
        font-size: 40px;
        font-weight: 500;
        letter-spacing: -.01em;
        line-height: 1.08;
        opacity: 0;
        transition: opacity var(--co-dur-crossfade) var(--co-ease-out);
    }

    #competenciesOverview .co-strength-average {
        color: var(--co-secondary);
        font-size: 12px;
        font-variant-numeric: lining-nums tabular-nums;
        padding-bottom: var(--co-space-1);
    }

    #competenciesOverview .co-stat-grid {
        display: grid;
        gap: var(--co-space-3);
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: var(--co-space-5);
    }

    #competenciesOverview .co-stat {
        border-left: 1px solid var(--co-border);
        min-width: 0;
        padding-left: var(--co-space-3);
    }

    #competenciesOverview .co-stat:first-child {
        border-left: 0;
        padding-left: 0;
    }

    #competenciesOverview .co-stat-label {
        color: var(--co-muted);
        display: block;
        font-size: 12px;
        line-height: 16px;
    }

    #competenciesOverview .co-stat-value {
        color: var(--co-text);
        display: block;
        font-size: 16px;
        font-variant-numeric: lining-nums tabular-nums;
        font-weight: 500;
        margin-top: var(--co-space-1);
    }

    #competenciesOverview .co-right-stack {
        display: grid;
        gap: var(--co-space-5);
        min-width: 0;
    }

    #competenciesOverview .co-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: var(--co-space-2);
        margin-bottom: var(--co-space-4);
    }

    #competenciesOverview .co-chip {
        background: var(--co-panel);
        border: 1px solid var(--co-border-strong);
        border-radius: var(--co-radius-control);
        color: var(--co-secondary);
        min-height: 44px;
        padding: var(--co-space-2) var(--co-space-4);
    }

    #competenciesOverview .co-chip[aria-pressed="true"] {
        background: var(--co-accent-soft);
        border-color: var(--co-accent);
        color: var(--co-accent-text);
        font-weight: 500;
    }

    #competenciesOverview .co-chip:hover {
        border-color: var(--co-border-strong);
    }

    #competenciesOverview .co-chip:focus-visible {
        background-color: var(--co-panel);
        border-color: var(--co-accent);
        color: var(--co-text);
    }

    #competenciesOverview .co-chip[aria-pressed="true"]:focus-visible {
        background-color: var(--co-accent-soft);
        border-color: var(--co-accent);
        color: var(--co-accent-text);
    }

    #competenciesOverview .co-skill-list {
        border-top: 1px solid var(--co-border);
        min-height: 48px;
        opacity: 0;
        position: relative;
        transition: opacity var(--co-dur-crossfade) var(--co-ease-out);
    }

    #competenciesOverview .co-loading-skeletons {
        display: none;
        inset: var(--co-space-2) 0 auto;
        pointer-events: none;
        position: absolute;
        z-index: 1;
    }

    #competenciesOverview[data-loading="true"] .co-loading-skeletons {
        display: grid;
        gap: var(--co-space-2);
    }

    #competenciesOverview .co-loading-skeleton {
        animation: co-skeleton-opacity var(--co-dur-slow) var(--co-ease-in-out) infinite alternate;
        background: rgba(142, 163, 187, .12);
        border-radius: var(--co-radius-control);
        height: 48px;
        opacity: .45;
    }

    @keyframes co-skeleton-opacity {
        to { opacity: .85; }
    }

    #competenciesOverview .co-skill-list.is-ready {
        opacity: 1;
    }

    #competenciesOverview .co-skill-list.is-transitioning {
        opacity: 0;
        pointer-events: none;
        transition-duration: var(--co-dur-list-out);
    }

    #competenciesOverview .co-row {
        align-items: center;
        background: transparent;
        border: 0;
        border-bottom: 1px solid var(--co-border);
        border-left: 2px solid transparent;
        border-radius: 0;
        color: var(--co-text);
        display: flex;
        gap: var(--co-space-3);
        justify-content: space-between;
        min-height: 48px;
        padding: var(--co-space-2) var(--co-space-2) var(--co-space-2) var(--co-space-3);
        text-align: left;
        width: 100%;
    }

    #competenciesOverview .co-row:hover {
        background: var(--co-raised);
    }

    #competenciesOverview .co-row[aria-pressed="true"] {
        background: var(--co-accent-soft);
        border-left-color: var(--co-accent);
    }

    #competenciesOverview .co-row:focus-visible {
        background-color: var(--co-raised);
        border-color: var(--co-border);
        border-left-color: var(--co-accent);
        color: var(--co-text);
    }

    #competenciesOverview .co-row[aria-pressed="true"]:focus-visible {
        background-color: var(--co-accent-soft);
    }

    #competenciesOverview .co-row-main {
        align-items: center;
        display: flex;
        gap: var(--co-space-2);
        min-width: 0;
    }

    #competenciesOverview .co-row-name {
        font-size: 14px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #competenciesOverview .co-row-category {
        color: var(--co-muted);
        font-size: 12px;
        white-space: nowrap;
    }

    #competenciesOverview .co-status {
        align-items: center;
        border: 1px solid transparent;
        border-radius: var(--co-radius-control);
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 12px;
        gap: var(--co-space-1);
        line-height: 18px;
        padding: var(--co-space-1) var(--co-space-2);
    }

    #competenciesOverview .co-status svg {
        height: 12px;
        width: 12px;
    }

    #competenciesOverview .co-status-verified {
        background: var(--co-verified-bg);
        border-color: rgba(134, 239, 172, .24);
        color: var(--co-verified);
    }

    #competenciesOverview .co-status-pending {
        background: var(--co-pending-bg);
        border-color: rgba(252, 211, 77, .24);
        color: var(--co-pending);
    }

    #competenciesOverview .co-status-rejected {
        background: var(--co-rejected-bg);
        border-color: rgba(253, 164, 175, .24);
        color: var(--co-rejected);
    }

    #competenciesOverview .co-status-neutral {
        background: var(--co-neutral-bg);
        border-color: var(--co-border);
        color: var(--co-neutral);
    }

    #competenciesOverview .co-empty {
        align-items: center;
        color: var(--co-secondary);
        display: flex;
        flex-direction: column;
        gap: var(--co-space-3);
        justify-content: center;
        min-height: 180px;
        padding: var(--co-space-5);
        text-align: center;
    }

    #competenciesOverview .co-empty-title {
        color: var(--co-text);
        font-size: 16px;
        font-weight: 500;
    }

    #competenciesOverview .co-empty-action,
    #competenciesOverview .co-primary {
        align-items: center;
        background: var(--co-accent);
        border: 1px solid var(--co-accent);
        border-radius: var(--co-radius-control);
        color: var(--co-text-on-accent);
        display: inline-flex;
        font-size: 14px;
        font-weight: 500;
        justify-content: center;
        min-height: 44px;
        padding: var(--co-space-2) var(--co-space-4);
        text-decoration: none;
    }

    #competenciesOverview .co-primary:hover,
    #competenciesOverview .co-empty-action:hover {
        background: var(--co-accent-hover);
        border-color: var(--co-accent-hover);
        color: var(--co-text-on-accent);
    }

    #competenciesOverview .co-primary:focus-visible,
    #competenciesOverview .co-empty-action:focus-visible {
        background-color: var(--co-accent);
        border-color: var(--co-accent);
        color: var(--co-text-on-accent);
    }

    #competenciesOverview .co-primary:active,
    #competenciesOverview .co-empty-action:active {
        background-color: var(--co-accent-hover);
        border-color: var(--co-accent-hover);
        color: var(--co-text-on-accent);
    }

    #competenciesOverview .co-detail {
        min-height: 280px;
    }

    #competenciesOverview .co-detail-content {
        opacity: 1;
        transform: translateY(0);
        transition: opacity var(--co-dur-detail) var(--co-ease-out), transform var(--co-dur-detail) var(--co-ease-out);
    }

    #competenciesOverview .co-detail-content.is-changing {
        opacity: 0;
        transform: translateY(var(--co-space-1));
    }

    #competenciesOverview .co-detail-head {
        align-items: flex-start;
        display: flex;
        flex-wrap: wrap;
        gap: var(--co-space-3);
        justify-content: space-between;
    }

    #competenciesOverview .co-detail-name {
        color: var(--co-text);
        font-size: 16px;
        font-weight: 500;
        letter-spacing: -.01em;
        overflow-wrap: anywhere;
    }

    #competenciesOverview .co-detail-tags {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: var(--co-space-2);
        margin-top: var(--co-space-2);
    }

    #competenciesOverview .co-category-tag {
        background: var(--co-accent-soft);
        border: 1px solid var(--co-border-strong);
        border-radius: var(--co-radius-control);
        color: var(--co-accent-text);
        font-size: 12px;
        padding: var(--co-space-1) var(--co-space-2);
    }

    #competenciesOverview .co-detail-description {
        color: var(--co-secondary);
        font-size: 13px;
        line-height: 1.5;
        margin-top: var(--co-space-4);
    }

    #competenciesOverview .co-levels {
        display: grid;
        gap: var(--co-space-3);
        margin-top: var(--co-space-5);
    }

    #competenciesOverview .co-level-row {
        align-items: center;
        display: grid;
        gap: var(--co-space-3);
        grid-template-columns: 96px 1fr 40px;
    }

    #competenciesOverview .co-level-name,
    #competenciesOverview .co-level-score {
        color: var(--co-secondary);
        font-size: 12px;
    }

    #competenciesOverview .co-level-score {
        font-variant-numeric: lining-nums tabular-nums;
        text-align: right;
    }

    #competenciesOverview .co-level-track {
        background: rgba(142, 163, 187, .17);
        border-radius: var(--co-radius-control);
        height: 4px;
        overflow: hidden;
    }

    #competenciesOverview .co-level-fill {
        background: var(--co-accent);
        border-radius: inherit;
        height: 100%;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform var(--co-dur-base) var(--co-ease-out);
    }

    #competenciesOverview .co-level-row.is-current .co-level-name,
    #competenciesOverview .co-level-row.is-current .co-level-score {
        color: var(--co-text);
        font-weight: 500;
    }

    #competenciesOverview .co-evidence {
        align-items: center;
        border-top: 1px solid var(--co-border);
        color: var(--co-secondary);
        display: flex;
        gap: var(--co-space-2);
        margin-top: var(--co-space-5);
        overflow-wrap: anywhere;
        padding-top: var(--co-space-3);
    }

    #competenciesOverview .co-evidence svg,
    #competenciesOverview .co-note-icon {
        color: var(--co-muted);
        flex: 0 0 auto;
        height: 16px;
        width: 16px;
    }

    #competenciesOverview .co-note {
        align-items: flex-start;
        background: rgba(127, 29, 29, .18);
        border: 1px solid rgba(253, 164, 175, .24);
        border-radius: var(--co-radius-control);
        color: var(--co-secondary);
        display: flex;
        gap: var(--co-space-2);
        margin-top: var(--co-space-4);
        padding: var(--co-space-3);
    }

    #competenciesOverview .co-note strong {
        color: var(--co-rejected);
        display: block;
        font-size: 12px;
        font-weight: 500;
        margin-bottom: var(--co-space-1);
    }

    #competenciesOverview .co-note-text {
        font-size: 13px;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    #competenciesOverview .co-detail-action {
        margin-top: var(--co-space-5);
    }

    #competenciesOverview .co-detail-empty {
        align-items: center;
        color: var(--co-muted);
        display: flex;
        justify-content: center;
        min-height: 232px;
        text-align: center;
    }

    #competenciesOverview .co-hidden {
        display: none !important;
    }

    #competenciesOverview .co-error {
        background: var(--co-rejected-bg);
        border: 1px solid var(--co-rejected);
        border-radius: var(--co-radius-control);
        color: var(--co-rejected);
        font-size: 13px;
        padding: var(--co-space-3);
    }

    #competenciesOverview .co-sr-only {
        clip: rect(0, 0, 0, 0);
        clip-path: inset(50%);
        height: 1px;
        overflow: hidden;
        position: absolute;
        white-space: nowrap;
        width: 1px;
    }

    @media (max-width: 900px) {
        #competenciesOverview .co-layout {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 991.98px) {
        .sb-main:has(#competenciesOverview) {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        #competenciesOverview {
            padding-top: var(--co-space-1);
        }

        #competenciesOverview .co-header {
            align-items: stretch;
            flex-direction: column;
        }

        #competenciesOverview .co-segment {
            width: 100%;
        }

        #competenciesOverview .co-segment-button {
            min-width: 0;
        }

        #competenciesOverview .co-outer-panel,
        #competenciesOverview .co-card {
            padding: var(--co-space-4);
        }

        #competenciesOverview .co-strength-level {
            font-size: 32px;
        }

        #competenciesOverview .co-stat-grid {
            gap: var(--co-space-2);
        }

        #competenciesOverview .co-stat {
            padding-left: var(--co-space-2);
        }

        #competenciesOverview .co-stat-label {
            font-size: 12px;
        }

        #competenciesOverview .co-chip-list {
            margin-left: calc(-1 * var(--co-space-1));
            margin-right: calc(-1 * var(--co-space-1));
        }

        #competenciesOverview .co-chip {
            padding-left: var(--co-space-3);
            padding-right: var(--co-space-3);
        }

        #competenciesOverview .co-axis-button[data-axis="soft"] {
            left: 0;
        }

        #competenciesOverview .co-axis-button[data-axis="certification"] {
            right: 0;
        }

        #competenciesOverview .co-row {
            gap: var(--co-space-2);
            padding-bottom: var(--co-space-1);
            padding-top: var(--co-space-1);
        }

        #competenciesOverview .co-row-main {
            align-items: flex-start;
            flex-direction: column;
            gap: var(--co-space-1);
            min-width: 0;
        }

        #competenciesOverview .co-row-name {
            max-width: 190px;
        }

        #competenciesOverview .co-level-row {
            grid-template-columns: 88px 1fr 36px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #competenciesOverview *,
        #competenciesOverview *::before,
        #competenciesOverview *::after {
            animation-duration: 1ms !important;
            animation-iteration-count: 1 !important;
            scroll-behavior: auto !important;
            transition-duration: 1ms !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $initialView = $overview['view'];
    $initialCategory = $overview['category_filter'];
    $initialScores = collect($overview['groups'])->mapWithKeys(fn ($group, $key) => [$key => $group['scores'][$initialView === 'all' ? 'all_submitted' : 'verified']])->all();
    $initialFiltered = collect($overview['skills'])->filter(function ($skill) use ($initialView, $initialCategory) {
        $visibleByStatus = $initialView === 'all'
            ? in_array($skill['status_group'], ['verified', 'pending', 'rejected', 'not_counted'], true)
            : $skill['status_group'] === 'verified';
        $visibleByCategory = ! $initialCategory
            || $skill['category_key'] === $initialCategory
            || $skill['group_key'] === $initialCategory;

        return $visibleByStatus && $visibleByCategory;
    })->values();
    $selectedSkill = $initialFiltered->first();
    $levelRows = [
        ['key' => 'beginner', 'label' => 'Beginner', 'points' => 25],
        ['key' => 'intermediate', 'label' => 'Intermediate', 'points' => 50],
        ['key' => 'advanced', 'label' => 'Advanced', 'points' => 75],
        ['key' => 'expert', 'label' => 'Expert', 'points' => 100],
    ];
@endphp
<main class="co-outer-panel" id="competenciesOverview"
    data-loading="true"
    aria-busy="true"
    data-view="{{ $initialView }}"
    data-category="{{ $initialCategory ?? '' }}"
    data-skills='@json($overview["skills"])'
    data-groups='@json($overview["groups"])'
    data-selected-id="{{ $selectedSkill['id'] ?? '' }}"
    data-edit-url-template="{{ route('student.competencies.edit', ['competency' => '__ID__'], false) }}">
    <header class="co-header">
        <div>
            <h1 class="co-title">My competencies</h1>
            <p class="co-hint">Your skills, evidence, and verified progress at a glance.</p>
        </div>
        <div class="co-segment" id="competencyViewToggle" role="group" aria-label="Competency view" data-view="{{ $initialView }}">
            <span class="co-segment-pill" aria-hidden="true"></span>
            <button type="button" class="co-segment-button" data-view-option="verified" aria-pressed="{{ $initialView === 'verified' ? 'true' : 'false' }}">Verified only</button>
            <button type="button" class="co-segment-button" data-view-option="all" aria-pressed="{{ $initialView === 'all' ? 'true' : 'false' }}">All submitted</button>
        </div>
    </header>

    <div class="co-layout">
        <section class="co-card" aria-labelledby="competencyRadarTitle">
            <h2 class="co-card-title" id="competencyRadarTitle">Competency profile</h2>
            <div class="co-radar-wrap">
                <svg class="co-radar" id="competencyRadar" viewBox="0 0 400 320" role="img"
                     aria-label="Competency scores: Technical {{ number_format($initialScores['technical'] ?? 0, 1) }} percent, Soft skills {{ number_format($initialScores['soft'] ?? 0, 1) }} percent, Certification {{ number_format($initialScores['certification'] ?? 0, 1) }} percent">
                    <defs>
                        <linearGradient id="competencyRadarFill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity=".26"></stop>
                            <stop offset="100%" stop-color="#38bdf8" stop-opacity=".06"></stop>
                        </linearGradient>
                    </defs>
                    <g class="co-grid-layer" id="radarGrid"></g>
                    <g class="co-axis-layer" id="radarAxes"></g>
                    <polygon id="radarSecondaryShape" class="co-secondary-shape" points="200,150 200,150 200,150"></polygon>
                    <polygon id="radarPrimaryShape" class="co-primary-shape" points="200,150 200,150 200,150"></polygon>
                    <g id="radarVertices"></g>
                </svg>
                <div class="co-vertex-tooltip" id="radarVertexTooltip" role="tooltip" aria-hidden="true"></div>
                <span class="co-sr-only" id="competencyChartAnnouncement" aria-live="polite" aria-atomic="true"></span>
                @foreach(['technical' => 'Technical', 'soft' => 'Soft skills', 'certification' => 'Certification'] as $axis => $label)
                    @php
                        $group = $overview['groups'][$axis];
                        $scoreKey = $initialView === 'all' ? 'all_submitted' : 'verified';
                        $axisPoints = collect($overview['skills'])->where('group_key', $axis)
                            ->filter(fn ($skill) => $initialView === 'all'
                                ? in_array($skill['status_group'], ['verified', 'pending'], true)
                                : $skill['status_group'] === 'verified')
                            ->sum('proficiency_points');
                    @endphp
                    <button type="button" class="co-axis-button" data-axis="{{ $axis }}" data-score="{{ $initialScores[$axis] ?? 0 }}"
                            aria-pressed="{{ $initialCategory === $axis ? 'true' : 'false' }}"
                            aria-label="{{ $label }}: {{ number_format($initialScores[$axis] ?? 0, 1) }} percent">
                        <span class="co-axis-name">{{ $label }}</span>
                        <span class="co-axis-value co-number">{{ number_format($initialScores[$axis] ?? 0, 1) }}%</span>
                        <span class="co-tooltip" role="tooltip">{{ $axisPoints }} of {{ $group['target'] * 100 }} points · {{ $group['counts']['pending'] }} pending</span>
                    </button>
                @endforeach
            </div>

            <section class="co-strength" aria-labelledby="profileStrengthLabel">
                <div class="co-overline" id="profileStrengthLabel">Profile strength</div>
                <div class="co-strength-main">
                    <div class="co-strength-level" id="profileStrengthLevel">{{ $overview['profile_strength']['level_label'] }}</div>
                    <div class="co-strength-average co-number" id="profileStrengthAverage">
                        @if($overview['profile_strength']['average_points'] !== null)
                            {{ number_format($overview['profile_strength']['average_points'] / 25, 1) }} avg
                        @else
                            No counted skills
                        @endif
                    </div>
                </div>
                <div class="co-stat-grid" aria-label="Category scores">
                    @foreach($overview['groups'] as $key => $group)
                        <div class="co-stat">
                            <span class="co-stat-label">{{ $group['label'] }}</span>
                            <span class="co-stat-value co-number" data-stat-value="{{ $key }}">{{ number_format($initialScores[$key] ?? 0, 1) }}%</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </section>

        <div class="co-right-stack">
            <section class="co-card" aria-labelledby="skillListTitle">
                <h2 class="co-card-title" id="skillListTitle">Skills and evidence</h2>
                <div class="co-chip-list" role="group" aria-label="Filter skills by category">
                    <button type="button" class="co-chip" data-category="" aria-pressed="{{ $initialCategory ? 'false' : 'true' }}">All categories</button>
                    @foreach($overview['groups'] as $key => $group)
                        <button type="button" class="co-chip" data-category="{{ $key }}" aria-pressed="{{ $initialCategory === $key ? 'true' : 'false' }}">{{ $group['label'] }}</button>
                    @endforeach
                    @if(! empty($overview['unmapped']['category_count']))
                        <button type="button" class="co-chip" data-category="__other" aria-pressed="{{ $initialCategory === '__other' ? 'true' : 'false' }}">Other categories</button>
                    @endif
                </div>

                @if($overview['skills'])
                    <div class="co-skill-list {{ $initialFiltered->isEmpty() ? 'co-hidden' : '' }}" id="competencySkillList" aria-live="polite">
                        <div class="co-loading-skeletons" aria-hidden="true">
                            <span class="co-loading-skeleton"></span>
                            <span class="co-loading-skeleton"></span>
                            <span class="co-loading-skeleton"></span>
                        </div>
                        @foreach($initialFiltered as $skill)
                            <button type="button" class="co-row" data-skill-id="{{ $skill['id'] }}" aria-pressed="{{ (string) ($selectedSkill['id'] ?? '') === (string) $skill['id'] ? 'true' : 'false' }}">
                                <span class="co-row-main">
                                    <span class="co-row-name">{{ $skill['name'] }}</span>
                                    <span class="co-row-category">{{ $skill['category_label'] }} · {{ $skill['proficiency_label'] }}</span>
                                </span>
                                @include('student.competencies.partials.status-badge', ['status' => $skill['status_group'], 'label' => $skill['status_label']])
                            </button>
                        @endforeach
                    </div>
                    <p class="co-empty {{ $initialFiltered->isEmpty() ? '' : 'co-hidden' }}" id="competencyNoResults">No competencies match this view.</p>
                @else
                    <div class="co-empty" id="competencyEmptyState">
                        <h3 class="co-empty-title">Build your competency profile</h3>
                        <p>Add a skill or certification and attach evidence for coordinator review.</p>
                        <a class="co-empty-action" href="{{ route('student.competencies.create') }}">Submit evidence</a>
                    </div>
                    <div class="co-skill-list co-hidden" id="competencySkillList" aria-live="polite">
                        <div class="co-loading-skeletons" aria-hidden="true">
                            <span class="co-loading-skeleton"></span>
                            <span class="co-loading-skeleton"></span>
                            <span class="co-loading-skeleton"></span>
                        </div>
                    </div>
                    <p class="co-empty co-hidden" id="competencyNoResults">No competencies match this view.</p>
                @endif
            </section>

            <section class="co-card co-detail" aria-labelledby="competencyDetailTitle">
                <h2 class="co-card-title" id="competencyDetailTitle">Selected competency</h2>
                <div class="co-detail-content" id="competencyDetailContent">
                    @if($selectedSkill)
                        <div class="co-detail-head">
                            <div>
                                <h3 class="co-detail-name" data-detail-name>{{ $selectedSkill['name'] }}</h3>
                                <div class="co-detail-tags">
                                    <span class="co-category-tag" data-detail-category>{{ $selectedSkill['category_label'] }}</span>
                                    @include('student.competencies.partials.status-badge', ['status' => $selectedSkill['status_group'], 'label' => $selectedSkill['status_label'], 'detail' => true])
                                </div>
                            </div>
                        </div>
                        <p class="co-detail-description" data-detail-description>{{ $selectedSkill['description'] }}</p>
                        <div class="co-levels" aria-label="Proficiency levels">
                            @foreach($levelRows as $level)
                                <div class="co-level-row {{ $selectedSkill['proficiency_key'] === $level['key'] ? 'is-current' : '' }}" data-level-row="{{ $level['key'] }}">
                                    <span class="co-level-name">{{ $level['label'] }}</span>
                                    <span class="co-level-track"><span class="co-level-fill" data-level-fill="{{ $level['key'] }}" data-value="{{ $selectedSkill['proficiency_points'] >= $level['points'] ? 100 : 0 }}" style="transform: scaleX({{ $selectedSkill['proficiency_points'] >= $level['points'] ? 1 : 0 }})"></span></span>
                                    <span class="co-level-score co-number">{{ $level['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="co-evidence" data-detail-evidence>
                            <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 1.75h5l3 3v9.5H4a1 1 0 0 1-1-1v-10a1.5 1.5 0 0 1 1-1.5Z" stroke="currentColor" stroke-width="1.3"/><path d="M9 2v3h3M5.5 8h5M5.5 10.5h5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                            <span>{{ $selectedSkill['evidence_filename'] ?: 'No evidence file attached' }}</span>
                        </div>
                        <div class="co-note {{ $selectedSkill['status_group'] === 'rejected' && $selectedSkill['coordinator_note'] ? '' : 'co-hidden' }}" data-detail-note>
                            <svg class="co-note-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 1.7a6.3 6.3 0 1 0 0 12.6A6.3 6.3 0 0 0 8 1.7Z" stroke="currentColor" stroke-width="1.3"/><path d="M8 7v3.4M8 4.8h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            <div><strong>Coordinator note</strong><p class="co-note-text">{{ $selectedSkill['coordinator_note'] }}</p></div>
                        </div>
                        <div class="co-detail-action">
                            <a class="co-primary" data-detail-action href="{{ route('student.competencies.edit', $selectedSkill['id']) }}">Edit competency</a>
                        </div>
                    @else
                        <div class="co-detail-empty" id="competencyDetailEmpty">Select a competency to view its details.</div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('competenciesOverview');
    if (!root) return;

    const svgNs = 'http://www.w3.org/2000/svg';
    const skills = JSON.parse(root.dataset.skills || '[]');
    const groups = JSON.parse(root.dataset.groups || '{}');
    const angleByGroup = { technical: -90, soft: 30, certification: 150 };
    const center = { x: 200, y: 150 };
    const radius = 82;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const duration = (token) => Number.parseFloat(getComputedStyle(root).getPropertyValue(token)) || 0;
    const rowStaggerMs = reduceMotion ? 0 : duration('--co-delay-row');
    const levelStaggerMs = reduceMotion ? 0 : duration('--co-delay-level');
    const initialRevealMs = reduceMotion ? 0 : duration('--co-dur-hero') / 2;
    const toggle = document.getElementById('competencyViewToggle');
    const radar = document.getElementById('competencyRadar');
    const radarWrap = radar.closest('.co-radar-wrap');
    const grid = document.getElementById('radarGrid');
    const axes = document.getElementById('radarAxes');
    const primaryShape = document.getElementById('radarPrimaryShape');
    const secondaryShape = document.getElementById('radarSecondaryShape');
    const vertices = document.getElementById('radarVertices');
    const vertexTooltip = document.getElementById('radarVertexTooltip');
    const chartAnnouncement = document.getElementById('competencyChartAnnouncement');
    const list = document.getElementById('competencySkillList');
    const noResults = document.getElementById('competencyNoResults');
    const detail = document.getElementById('competencyDetailContent');
    const statusIcons = {
        verified: '<svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m2.3 6.1 2.2 2.2 5.2-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        pending: '<svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><circle cx="6" cy="6" r="4.4" stroke="currentColor" stroke-width="1.2"/><path d="M6 3.4v2.8l1.8 1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>',
        rejected: '<svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="m3 3 6 6m0-6L3 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        not_counted: '<svg viewBox="0 0 12 12" fill="none" aria-hidden="true"><circle cx="6" cy="6" r="4.4" stroke="currentColor" stroke-width="1.2"/><path d="M6 5.2v2.1M6 3.6h.01" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>'
    };
    const statusClasses = {
        verified: 'co-status-verified',
        pending: 'co-status-pending',
        rejected: 'co-status-rejected',
        not_counted: 'co-status-neutral'
    };
    const levels = [
        { key: 'beginner', label: 'Beginner', points: 25 },
        { key: 'intermediate', label: 'Intermediate', points: 50 },
        { key: 'advanced', label: 'Advanced', points: 75 },
        { key: 'expert', label: 'Expert', points: 100 }
    ];
    let view = root.dataset.view === 'all' ? 'all' : 'verified';
    let category = root.dataset.category || '';
    let selectedId = root.dataset.selectedId || '';
    let currentScores = {};
    let activeTweens = new WeakMap();
    let announceTimer = 0;
    let strengthLabelTimer = 0;
    let listRenderTimer = 0;
    let listRenderVersion = 0;
    let hydrationTimer = 0;

    const finishHydration = () => {
        window.clearTimeout(hydrationTimer);
        root.dataset.loading = 'false';
        root.setAttribute('aria-busy', 'false');
    };

    const groupScore = (group, selectedView) => Number(
        groups[group]?.scores?.[selectedView === 'all' ? 'all_submitted' : 'verified'] ?? 0
    );

    const fmt = (value) => `${Number(value).toFixed(1)}%`;
    let currentAveragePoints = 0;
    const point = (angle, value) => {
        const radians = angle * Math.PI / 180;
        const distance = radius * Math.max(0, Math.min(100, Number(value))) / 100;
        return { x: center.x + Math.cos(radians) * distance, y: center.y + Math.sin(radians) * distance };
    };
    const pointsFor = (value) => Object.keys(angleByGroup)
        .map((key) => point(angleByGroup[key], typeof value === 'number' ? value : (value[key] || 0)))
        .map((pointValue) => `${pointValue.x.toFixed(2)},${pointValue.y.toFixed(2)}`)
        .join(' ');

    const tween = (element, from, to, duration, easing, update) => {
        const previous = activeTweens.get(element);
        if (previous) cancelAnimationFrame(previous);
        if (reduceMotion) {
            update(to);
            return;
        }
        const start = performance.now();
        const frame = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            update(from + (to - from) * easing(progress));
            if (progress < 1) {
                activeTweens.set(element, requestAnimationFrame(frame));
            } else {
                activeTweens.delete(element);
                update(to);
            }
        };
        activeTweens.set(element, requestAnimationFrame(frame));
    };
    const cancelTween = (element) => {
        const previous = activeTweens.get(element);
        if (previous) cancelAnimationFrame(previous);
        activeTweens.delete(element);
    };
    const easeOut = (value) => 1 - Math.pow(1 - value, 3);
    const easeInOut = (value) => value < .5 ? 4 * value * value * value : 1 - Math.pow(-2 * value + 2, 3) / 2;

    const updatePolygon = (shape, scores) => { shape.setAttribute('points', pointsFor(scores)); };
    const buildRadarGrid = () => {
        [33, 67, 100].forEach((value) => {
            const ring = document.createElementNS(svgNs, 'polygon');
            ring.setAttribute('class', `co-ring${value === 100 ? ' co-ring-outer' : ''}`);
            ring.setAttribute('points', pointsFor(value));
            grid.append(ring);
        });
        Object.keys(angleByGroup).forEach((key) => {
            const endpoint = point(angleByGroup[key], 100);
            const axis = document.createElementNS(svgNs, 'line');
            axis.setAttribute('class', 'co-axis-line');
            axis.setAttribute('x1', center.x);
            axis.setAttribute('y1', center.y);
            axis.setAttribute('x2', endpoint.x.toFixed(2));
            axis.setAttribute('y2', endpoint.y.toFixed(2));
            axes.append(axis);
        });
    };
    const announceChart = (scores) => {
        const label = `Competency scores: Technical ${fmt(scores.technical)}, Soft skills ${fmt(scores.soft)}, Certification ${fmt(scores.certification)}`;
        radar.setAttribute('aria-label', label);
        chartAnnouncement.textContent = label;
    };
    const vertexTooltipText = (key) => {
        const groupSkills = skills.filter((skill) => skill.group_key === key);
        const counted = groupSkills.filter((skill) => skill.status_group === 'verified'
            || (view === 'all' && skill.status_group === 'pending'));
        const pending = groupSkills.filter((skill) => skill.status_group === 'pending').length;
        const points = counted.reduce((sum, skill) => sum + Number(skill.proficiency_points || 0), 0);
        return `${points} of ${Number(groups[key]?.target || 0) * 100} points · ${pending} pending`;
    };
    const showVertexTooltip = (key) => {
        const coordinates = point(angleByGroup[key], currentScores[key] || 0);
        const wrapRect = radarWrap.getBoundingClientRect();
        const svgRect = radar.getBoundingClientRect();
        const tooltipWidth = vertexTooltip.offsetWidth;
        const angle = angleByGroup[key] * Math.PI / 180;
        const inwardX = Math.cos(angle) * 36;
        const inwardY = Math.sin(angle) * 36;
        const x = svgRect.left - wrapRect.left + (coordinates.x / 400) * svgRect.width - inwardX;
        const y = svgRect.top - wrapRect.top + (coordinates.y / 320) * svgRect.height - inwardY;
        const safeX = Math.max(tooltipWidth / 2 + 8, Math.min(wrapRect.width - tooltipWidth / 2 - 8, x));
        vertexTooltip.textContent = vertexTooltipText(key);
        vertexTooltip.style.left = `${safeX}px`;
        vertexTooltip.style.top = `${y}px`;
        vertexTooltip.classList.add('is-visible');
    };
    const hideVertexTooltip = () => vertexTooltip.classList.remove('is-visible');

    const makeStatus = (status, label) => {
        const badge = document.createElement('span');
        badge.className = `co-status ${statusClasses[status] || statusClasses.not_counted}`;
        badge.innerHTML = statusIcons[status] || statusIcons.not_counted;
        const text = document.createElement('span');
        text.textContent = label;
        badge.append(text);
        return badge;
    };

    const renderDetail = (skill) => {
        if (!skill) {
            detail.innerHTML = '<div class="co-detail-empty">Select a competency to view its details.</div>';
            return;
        }

        detail.innerHTML = '';
        const content = document.createElement('div');
        content.className = 'co-detail-content is-changing';
        const head = document.createElement('div');
        head.className = 'co-detail-head';
        const headingWrap = document.createElement('div');
        const name = document.createElement('h3');
        name.className = 'co-detail-name';
        name.textContent = skill.name;
        headingWrap.append(name);
        const tags = document.createElement('div');
        tags.className = 'co-detail-tags';
        const categoryTag = document.createElement('span');
        categoryTag.className = 'co-category-tag';
        categoryTag.textContent = skill.category_label;
        tags.append(categoryTag, makeStatus(skill.status_group, skill.status_label));
        headingWrap.append(tags);
        head.append(headingWrap);
        content.append(head);

        if (skill.description) {
            const description = document.createElement('p');
            description.className = 'co-detail-description';
            description.textContent = skill.description;
            content.append(description);
        }

        const levelList = document.createElement('div');
        levelList.className = 'co-levels';
        levelList.setAttribute('aria-label', 'Proficiency levels');
        levels.forEach((level, index) => {
            const row = document.createElement('div');
            row.className = `co-level-row${skill.proficiency_key === level.key ? ' is-current' : ''}`;
            const label = document.createElement('span');
            label.className = 'co-level-name';
            label.textContent = level.label;
            const track = document.createElement('span');
            track.className = 'co-level-track';
            const fill = document.createElement('span');
            fill.className = 'co-level-fill';
            fill.style.transitionDelay = `${index * levelStaggerMs}ms`;
            fill.style.transform = 'scaleX(0)';
            track.append(fill);
            const score = document.createElement('span');
            score.className = 'co-level-score co-number';
            score.textContent = String(level.points);
            row.append(label, track, score);
            levelList.append(row);
        });
        content.append(levelList);

        const evidence = document.createElement('div');
        evidence.className = 'co-evidence';
        evidence.innerHTML = '<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 1.75h5l3 3v9.5H4a1 1 0 0 1-1-1v-10a1.5 1.5 0 0 1 1-1.5Z" stroke="currentColor" stroke-width="1.3"/><path d="M9 2v3h3M5.5 8h5M5.5 10.5h5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>';
        const evidenceName = document.createElement('span');
        evidenceName.textContent = skill.evidence_filename || 'No evidence file attached';
        evidence.append(evidenceName);
        content.append(evidence);

        if (skill.status_group === 'rejected' && skill.coordinator_note) {
            const note = document.createElement('div');
            note.className = 'co-note';
            note.innerHTML = '<svg class="co-note-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 1.7a6.3 6.3 0 1 0 0 12.6A6.3 6.3 0 0 0 8 1.7Z" stroke="currentColor" stroke-width="1.3"/><path d="M8 7v3.4M8 4.8h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
            const noteContent = document.createElement('div');
            const noteTitle = document.createElement('strong');
            noteTitle.textContent = 'Coordinator note';
            const noteText = document.createElement('p');
            noteText.className = 'co-note-text';
            noteText.textContent = skill.coordinator_note;
            noteContent.append(noteTitle, noteText);
            note.append(note.firstElementChild, noteContent);
            content.append(note);
        }

        const actionWrap = document.createElement('div');
        actionWrap.className = 'co-detail-action';
        const action = document.createElement('a');
        action.className = 'co-primary';
        action.href = root.dataset.editUrlTemplate.replace('__ID__', encodeURIComponent(skill.id));
        action.textContent = 'Edit competency';
        actionWrap.append(action);
        content.append(actionWrap);
        detail.append(content);
        const revealContent = () => {
            content.classList.remove('is-changing');
            content.querySelectorAll('.co-level-fill').forEach((fill, index) => {
                const level = levels[index];
                fill.style.transform = `scaleX(${skill.proficiency_points >= level.points ? 1 : 0})`;
            });
        };
        if (reduceMotion) {
            revealContent();
        } else {
            requestAnimationFrame(revealContent);
        }
    };

    const skillVisible = (skill) => {
        const statusVisible = view === 'verified'
            ? skill.status_group === 'verified'
            : ['verified', 'pending', 'rejected', 'not_counted'].includes(skill.status_group);
        const categoryVisible = !category
            || (category === '__other' ? !skill.group_key : skill.group_key === category);
        return statusVisible && categoryVisible;
    };

    const renderList = (animate = true, initial = false) => {
        if (!list) return;
        window.clearTimeout(listRenderTimer);
        const version = ++listRenderVersion;
        const commit = () => {
            if (version !== listRenderVersion) return;
            const visible = skills.filter(skillVisible);
            const skeletons = list.querySelector('.co-loading-skeletons');
            list.replaceChildren(...(skeletons ? [skeletons] : []));
            visible.forEach((skill, index) => {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'co-row';
                row.dataset.skillId = String(skill.id);
                row.setAttribute('aria-pressed', String(String(skill.id) === String(selectedId)));
                const main = document.createElement('span');
                main.className = 'co-row-main';
                const name = document.createElement('span');
                name.className = 'co-row-name';
                name.textContent = skill.name;
                const meta = document.createElement('span');
                meta.className = 'co-row-category';
                meta.textContent = `${skill.category_label} · ${skill.proficiency_label}`;
                main.append(name, meta);
                row.append(main, makeStatus(skill.status_group, skill.status_label));
                row.style.opacity = '0';
                row.style.transition = 'opacity var(--co-dur-crossfade) var(--co-ease-out)';
                row.style.transitionDelay = `${(initial ? initialRevealMs : 0) + Math.min(index, 7) * rowStaggerMs}ms`;
                row.addEventListener('click', () => selectSkill(skill.id));
                list.append(row);
            });
            const isEmpty = visible.length === 0;
            list.classList.toggle('co-hidden', isEmpty);
            if (noResults) noResults.classList.toggle('co-hidden', !isEmpty || skills.length === 0);
            list.classList.remove('is-transitioning');
            list.classList.toggle('is-ready', reduceMotion || !animate);
            if (!visible.some((skill) => String(skill.id) === String(selectedId))) {
                selectedId = visible[0]?.id ?? '';
            }
            list.querySelectorAll('[data-skill-id]').forEach((row) => {
                row.setAttribute('aria-pressed', String(row.dataset.skillId === String(selectedId)));
            });
            renderDetail(skills.find((skill) => String(skill.id) === String(selectedId)));
            if (reduceMotion) {
                list.querySelectorAll('.co-row').forEach((row) => { row.style.opacity = '1'; });
            } else {
                requestAnimationFrame(() => {
                    if (version !== listRenderVersion) return;
                    list.classList.add('is-ready');
                    list.querySelectorAll('.co-row').forEach((row) => { row.style.opacity = '1'; });
                });
            }
        };

        if (animate && !reduceMotion && list.querySelector('[data-skill-id]')) {
            list.classList.add('is-transitioning');
            listRenderTimer = window.setTimeout(commit, duration('--co-dur-list-out'));
            return;
        }
        list.classList.remove('is-transitioning');
        commit();
    };

    const updateStrength = (initial = false) => {
        const counted = skills.filter((skill) => {
            const categoryVisible = !category
                || (category === '__other' ? !skill.group_key : skill.category_key === category || skill.group_key === category);
            const statusVisible = skill.status_group === 'verified'
                || (view === 'all' && skill.status_group === 'pending');
            return categoryVisible && statusVisible;
        });
        const average = counted.length
            ? counted.reduce((sum, skill) => sum + Number(skill.proficiency_points || 0), 0) / counted.length
            : null;
        const levelLabel = average === null ? 'No counted skills'
            : average < 37.5 ? 'Beginner'
                : average < 62.5 ? 'Intermediate'
                    : average < 87.5 ? 'Advanced' : 'Expert';
        const level = document.getElementById('profileStrengthLevel');
        const averageText = document.getElementById('profileStrengthAverage');
        if (level) {
            window.clearTimeout(strengthLabelTimer);
            if (!reduceMotion) level.style.opacity = '0';
            strengthLabelTimer = window.setTimeout(() => {
                level.textContent = levelLabel;
                level.style.opacity = '1';
            }, reduceMotion ? 0 : (initial ? initialRevealMs : duration('--co-dur-selection')));
        }
        if (averageText) {
            if (average === null) {
                cancelTween(averageText);
                currentAveragePoints = 0;
                averageText.textContent = 'No counted skills';
            } else {
                tween(averageText, currentAveragePoints, average, reduceMotion ? 0 : (initial ? duration('--co-dur-hero') : duration('--co-dur-slow')), initial ? easeOut : easeInOut, (value) => {
                    currentAveragePoints = value;
                    averageText.textContent = `${(value / 25).toFixed(1)} avg`;
                });
            }
        }
    };

    const updateScores = () => {
        window.clearTimeout(announceTimer);
        const targets = Object.keys(angleByGroup).reduce((result, key) => {
            result[key] = groupScore(key, view);
            return result;
        }, {});

        Object.keys(angleByGroup).forEach((key) => {
            const axisButton = document.querySelector(`#competenciesOverview [data-axis="${key}"]`);
            const valueElement = axisButton?.querySelector('.co-axis-value');
            const stat = document.querySelector(`#competenciesOverview [data-stat-value="${key}"]`);
            const from = Number(currentScores[key] ?? 0);
            tween(axisButton || radar, from, targets[key], duration('--co-dur-slow'), easeInOut, (value) => {
                currentScores[key] = value;
                if (valueElement) valueElement.textContent = fmt(value);
                if (stat) stat.textContent = fmt(value);
                updatePolygon(primaryShape, currentScores);
                updateVertices(currentScores);
            });
            if (axisButton) {
                axisButton.dataset.score = String(targets[key]);
                axisButton.setAttribute('aria-label', `${groups[key].label}: ${fmt(targets[key])} percent`);
                const tooltip = axisButton.querySelector('.co-tooltip');
                const groupSkills = skills.filter((skill) => skill.group_key === key);
                const counted = groupSkills.filter((skill) => skill.status_group === 'verified'
                    || (view === 'all' && skill.status_group === 'pending'));
                const pendingCount = groupSkills.filter((skill) => skill.status_group === 'pending').length;
                if (tooltip) tooltip.textContent = `${counted.reduce((sum, skill) => sum + skill.proficiency_points, 0)} of ${groups[key].target * 100} points · ${pendingCount} pending`;
            }
        });
        announceTimer = window.setTimeout(() => announceChart(targets), reduceMotion ? 0 : duration('--co-dur-slow'));
    };

    const selectSkill = (id) => {
        selectedId = id;
        list?.querySelectorAll('[data-skill-id]').forEach((row) => {
            row.setAttribute('aria-pressed', String(row.dataset.skillId === String(id)));
        });
        renderDetail(skills.find((skill) => String(skill.id) === String(id)));
    };

    const setCategory = (key) => {
        finishHydration();
        category = category === key ? '' : key;
        document.querySelectorAll('#competenciesOverview [data-category]').forEach((chip) => {
            chip.setAttribute('aria-pressed', String(chip.dataset.category === category));
        });
        document.querySelectorAll('#competenciesOverview [data-axis]').forEach((axis) => {
            axis.setAttribute('aria-pressed', String(axis.dataset.axis === category));
        });
        updateVertices(currentScores);
        if (category) pulseVertex(category);
        updateStrength();
        renderList();
    };

    document.querySelectorAll('#competenciesOverview [data-view-option]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextView = button.dataset.viewOption;
            if (nextView === view) return;
            finishHydration();
            view = nextView;
            toggle.dataset.view = view;
            root.dataset.view = view;
            document.querySelectorAll('#competenciesOverview [data-view-option]').forEach((option) => {
                option.setAttribute('aria-pressed', String(option.dataset.viewOption === view));
            });
            updateScores();
            updateStrength();
            renderList();
        });
    });

    document.querySelectorAll('#competenciesOverview [data-category]').forEach((button) => {
        button.addEventListener('click', () => setCategory(button.dataset.category));
    });

    document.querySelectorAll('#competenciesOverview [data-axis]').forEach((button) => {
        button.addEventListener('click', () => setCategory(button.dataset.axis));
    });

    const initialTargets = Object.keys(angleByGroup).reduce((result, key) => {
        result[key] = groupScore(key, view);
        return result;
    }, {});
    const initialCurrent = { technical: 0, soft: 0, certification: 0 };
    const submittedTargets = Object.keys(angleByGroup).reduce((result, key) => {
        result[key] = groupScore(key, 'all');
        return result;
    }, {});
    currentScores = { ...initialCurrent };
    buildRadarGrid();
    updatePolygon(primaryShape, initialCurrent);
    updatePolygon(secondaryShape, submittedTargets);
    Object.keys(angleByGroup).forEach((key) => {
        const vertex = document.createElementNS(svgNs, 'circle');
        vertex.setAttribute('class', 'co-vertex');
        vertex.dataset.vertex = key;
        const halo = document.createElementNS(svgNs, 'circle');
        halo.setAttribute('class', 'co-vertex-halo');
        halo.dataset.halo = key;
        const hitArea = document.createElementNS(svgNs, 'circle');
        hitArea.setAttribute('class', 'co-vertex-hit');
        hitArea.setAttribute('aria-hidden', 'true');
        hitArea.dataset.vertexHit = key;
        hitArea.addEventListener('pointerenter', () => showVertexTooltip(key));
        hitArea.addEventListener('pointerleave', hideVertexTooltip);
        hitArea.addEventListener('pointercancel', hideVertexTooltip);
        vertices.append(halo, vertex, hitArea);
    });
    const updateVertices = (scores) => Object.keys(angleByGroup).forEach((key) => {
        const coordinates = point(angleByGroup[key], scores[key] || 0);
        const vertex = vertices.querySelector(`[data-vertex="${key}"]`);
        const halo = vertices.querySelector(`[data-halo="${key}"]`);
        const hitArea = vertices.querySelector(`[data-vertex-hit="${key}"]`);
        [vertex, halo, hitArea].forEach((item) => {
            item?.setAttribute('cx', coordinates.x);
            item?.setAttribute('cy', coordinates.y);
        });
        hitArea?.classList.toggle('is-inactive', Number(scores[key] || 0) <= 0);
        halo?.classList.toggle('is-selected', category === key);
    });
    const pulseVertex = (key) => {
        const vertex = vertices.querySelector(`[data-vertex="${key}"]`);
        if (!vertex || reduceMotion) return;
        vertex.getAnimations().forEach((animation) => animation.cancel());
        vertex.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.35)' }, { transform: 'scale(1)' }],
            {
                duration: duration('--co-dur-pulse'),
                easing: getComputedStyle(root).getPropertyValue('--co-ease-out').trim()
            }
        );
    };
    const refreshShapeAndVertices = () => {
        updatePolygon(primaryShape, currentScores);
        updateVertices(currentScores);
    };

    Object.keys(angleByGroup).forEach((key) => {
        currentScores[key] = 0;
        const axisButton = document.querySelector(`#competenciesOverview [data-axis="${key}"]`);
        const currentValue = () => Number(currentScores[key] || 0);
        tween(axisButton || radar, currentValue(), initialTargets[key], duration('--co-dur-hero'), easeOut, (value) => {
            currentScores[key] = value;
            refreshShapeAndVertices();
            axisButton?.querySelector('.co-axis-value') && (axisButton.querySelector('.co-axis-value').textContent = fmt(value));
            const stat = document.querySelector(`#competenciesOverview [data-stat-value="${key}"]`);
            if (stat) stat.textContent = fmt(value);
        });
    });
    radar.classList.add('is-ready');
    updateStrength(true);
    renderList(false, true);
    announceTimer = window.setTimeout(() => announceChart(initialTargets), reduceMotion ? 0 : duration('--co-dur-hero'));
    if (initialRevealMs > 0) {
        hydrationTimer = window.setTimeout(finishHydration, initialRevealMs + duration('--co-dur-crossfade'));
    } else {
        finishHydration();
    }
})();
</script>
@endpush
