<script src="https://cdn.jsdelivr.net/npm/pace-js@latest/pace.min.js"></script>

<style>
    .pace {
        -webkit-pointer-events: none;
        pointer-events: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none
    }

    .pace-inactive {
        display: none
    }

    .pace .pace-progress {
        background: #3b82f6;
        position: fixed;
        z-index: 2000;
        top: 0;
        right: 100%;
        width: 100%;
        height: 3px
    }

    nav span.truncate {
        overflow: visible !important;
    }
</style>
