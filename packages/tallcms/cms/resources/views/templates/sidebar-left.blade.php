{{-- Sidebar Left Template: Content with configurable left sidebar --}}
<div class="sidebar-layout sidebar-left">
    <div class="sidebar-layout-container">
        {{-- Left sidebar --}}
        <aside class="sidebar-layout-sidebar">
            <div class="sidebar-layout-sidebar-inner">
                <x-tallcms::widgets.sidebar :page="$page" :widgets="$sidebarWidgets" :rendered-content="$renderedContent" />
            </div>
        </aside>

        {{-- Main content --}}
        <main class="sidebar-layout-content">
            <section id="content">
                {!! $renderedContent !!}
            </section>

            {{-- SPA Mode: Additional pages as sections --}}
            @foreach($allPages as $pageData)
                <section id="{{ $pageData['anchor'] }}">
                    {!! $pageData['content'] !!}
                </section>
            @endforeach
        </main>
    </div>
</div>

<style>
    .sidebar-layout { width: 100%; }
    .sidebar-layout-container {
        max-width: 80rem;
        margin: 0 auto;
        padding: 2rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 2rem;
        align-items: flex-start;
    }
    .sidebar-layout-content {
        flex: 1;
        min-width: 0;
    }
    .sidebar-layout-content > section > * {
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0 !important;
    }
    .sidebar-layout-sidebar {
        width: 100%;
    }
    .sidebar-layout-sidebar-inner {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    @media (min-width: 1024px) {
        .sidebar-layout-container {
            flex-direction: row;
            padding: 2rem;
            align-items: flex-start;
        }
        .sidebar-layout-sidebar {
            width: 20rem;
            flex-shrink: 0;
        }
        .sidebar-left .sidebar-layout-sidebar {
            order: -1;
        }
        .sidebar-layout-sidebar-inner {
            position: sticky;
            top: 6rem;
        }
    }
</style>
