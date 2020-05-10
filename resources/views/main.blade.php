<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'WebPage') }}</title>
    <link rel="stylesheet" type="text/css" href="{{ mix('css/app.css') }}"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/view-design@4.2.0/dist/styles/iview.css">
    <script src="//cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"></script>
    <script>
        window.csrfToken = { csrfToken : "{{ csrf_token() }}" };
    </script>
</head>
<body>

@extends('ie')
<div id="app">
    <div class="app-view-loading">
        <div>
            <div>PAGE LOADING</div>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>

<script src="{{ mix('js/app.js') }}?__={{ $version }}"></script>

</body>
</html>
