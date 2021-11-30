window.addEventListener('load', function () {
  var manager = iframemanager();
  manager.run({
    currLang: 'x',
    services: {
      youtube: {
        embedUrl: 'https://www.youtube-nocookie.com/embed/{data-id}',
        thumbnailUrl: 'https://i3.ytimg.com/vi/{data-id}/hqdefault.jpg',
        iframe: {
          allow: 'accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen;',
        },
        cookie: {
          name: 'cc_youtube'
        },
        languages: {
          x: {
            notice: props.l10n_notice.replace('SITE', 'youtube.com').replace('3PARTYURL', 'https://www.youtube.com/t/terms'),
            loadBtn: props.l10n_loadBtn,
            loadAllBtn: props.l10n_loadAllBtn
          },
        }
      },
      vimeo: {
        embedUrl: 'https://player.vimeo.com/video/{data-id}',
        thumbnailUrl: function (id, setThumbnail) {
          var url = 'https://vimeo.com/api/v2/video/' + id + '.json';
          var xhttp = new XMLHttpRequest();
          xhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
              var src = JSON.parse(this.response)[0].thumbnail_large;
              setThumbnail(src);
            }
          };
          xhttp.open('GET', url, true);
          xhttp.send();
        },
        iframe: {
          allow: 'accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen;',
        },
        cookie: {
          name: 'cc_vimeo'
        },
        languages: {
          x: {
            notice: props.l10n_notice.replace('SITE', 'vimeo.com').replace('3PARTYURL', 'https://vimeo.com/terms'),
            loadBtn: props.l10n_loadBtn,
            loadAllBtn: props.l10n_loadAllBtn
          }
        }
      }
    }
  });
});
