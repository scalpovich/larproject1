@inject('menu', 'Tools\Menu')

@extends('layouts.app')
@section('content')
    <div class="col-md-9">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Danh sách bài hát</h3>
            </div>
            <div class="panel-body">
                <div style="text-align: center">
                    {!! $songs->links() !!}
                    <a href="{{url('song/create')}}">
                        <button class="btn btn-success">
                            Thêm bài hát mới
                        </button>
                    </a>
                </div>
                {{--Bang: nghe si--}}
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hành động</th>
                            <th>Tên bài hát</th>
                            <th>Nghệ sĩ</th>
                            <th>Thể loại</th>
                            <th>Lượt nghe</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($songs as $song)
                            <tr>
                                <td>{{$song->id}}</td>
                                <td><a href="{{url("song/$song->id/edit")}}">Sửa</a> - <a
                                            href="{{url("song/$song->id/delete")}}">Xóa</a></td>
                                <td>
                                    <a href="{{url("bai-hat/$song->song_title_slug.html")}}" target="_blank">
                                        {{$song->song_title}}
                                    </a>
                                </td>
                                <td>
                                    @foreach($song->artists as $artist)
                                        <a href="{{url("artist/$artist->id/edit")}}">{{$artist->artist_title}}</a>
                                    @endforeach
                                </td>
                                <td><a href="{!! url("cate/".$song->cate->id."/edit") !!}">{{$song->cate->cate_title}}</a></td>
                                <td>{{$song->song_view}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{--/ Bang: nghe si--}}

            </div>
        </div>
    </div>
@stop
