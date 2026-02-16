(() => {
  const body = document.body;
  const menuToggle = document.querySelector('.menu-toggle');
  const themeToggle = document.querySelector('.theme-toggle');
  const searchToggle = document.querySelector('.search-toggle');

  const menuOpenLabel = menuToggle
    ? (menuToggle.getAttribute('data-open-label') || (menuToggle.textContent ? menuToggle.textContent.trim() : '') || 'เมนู')
    : '';
  const menuCloseLabel = menuToggle ? menuToggle.getAttribute('data-close-label') || 'ปิด' : '';

  const setMenuOpen = (isOpen) => {
    if (!menuToggle) {
      return;
    }
    body.classList.toggle('menu-open', isOpen);
    menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    if (menuOpenLabel) {
      menuToggle.textContent = isOpen ? menuCloseLabel : menuOpenLabel;
    }
  };

  const setSearchOpen = (isOpen) => {
    if (!searchToggle) {
      return;
    }
    body.classList.toggle('search-open', isOpen);
    searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  };

  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      const isOpen = !body.classList.contains('menu-open');
      setSearchOpen(false);
      setMenuOpen(isOpen);
    });
  }

  if (searchToggle) {
    searchToggle.addEventListener('click', () => {
      const isOpen = !body.classList.contains('search-open');
      setMenuOpen(false);
      setSearchOpen(isOpen);
      if (isOpen) {
        const searchInput = document.querySelector('.main-nav .search-form input[type="search"]');
        if (searchInput) {
          setTimeout(() => searchInput.focus(), 50);
        }
      }
    });
  }

  if (themeToggle && window.localStorage) {
    themeToggle.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('pva-theme', next);
    });
  }

  const copyButtons = document.querySelectorAll('[data-copy-link]');
  copyButtons.forEach((btn) => {
    const defaultLabel = btn.getAttribute('data-default-label') || (btn.textContent ? btn.textContent.trim() : '') || 'คัดลอกลิงก์';
    const successLabel = btn.getAttribute('data-success-label') || 'คัดลอกแล้ว';
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(window.location.href);
        btn.textContent = successLabel;
        setTimeout(() => {
          btn.textContent = defaultLabel;
        }, 1500);
      } catch (err) {
        window.alert('ไม่สามารถคัดลอกลิงก์ได้');
      }
    });
  });

  const saveButtons = document.querySelectorAll('[data-save-video]');
  saveButtons.forEach((btn) => {
    const savedLabel = btn.getAttribute('data-saved-label') || 'บันทึกแล้ว';
    const unsavedLabel = btn.getAttribute('data-unsaved-label') || (btn.textContent ? btn.textContent.trim() : '') || 'บันทึกไว้ดู';
    btn.addEventListener('click', () => {
      btn.classList.toggle('is-saved');
      btn.textContent = btn.classList.contains('is-saved') ? savedLabel : unsavedLabel;
    });
  });

  const sortBar = document.querySelector('[data-sort-bar]');
  if (sortBar) {
    sortBar.addEventListener('click', (event) => {
      const target = event.target.closest('[data-sort]');
      if (!target) {
        return;
      }
      const sort = target.getAttribute('data-sort');
      sortBar.querySelectorAll('.filter-tab').forEach((tab) => tab.classList.remove('is-active'));
      target.classList.add('is-active');
      const grid = document.querySelector('[data-grid]');
      if (grid) {
        grid.dataset.sort = sort;
        grid.dataset.page = '1';
        loadMore(grid, 1, sort, grid.dataset.taxonomy, grid.dataset.term, true);
      }
    });
  }

  const loadMoreButtons = document.querySelectorAll('[data-load-more]');
  loadMoreButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const grid = btn.closest('section')?.querySelector('[data-grid]') || document.querySelector('[data-grid]');
      if (!grid) {
        return;
      }
      const page = parseInt(grid.dataset.page || '1', 10) + 1;
      const sort = grid.dataset.sort || 'latest';
      loadMore(grid, page, sort, btn.dataset.taxonomy || grid.dataset.taxonomy, btn.dataset.term || grid.dataset.term, false, btn);
    });

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            btn.click();
          }
        });
      }, { rootMargin: '200px' });
      observer.observe(btn);
    }
  });

  function loadMore(grid, page, sort, taxonomy, term, replace = false, btn = null) {
    if (!window.pvaSettings) {
      return;
    }
    const button = btn || document.querySelector('[data-load-more]');
    if (button) {
      button.disabled = true;
      button.textContent = pvaSettings.loadingLabel;
    }

    const form = new URLSearchParams();
    form.append('action', 'pva_load_more');
    form.append('nonce', pvaSettings.nonce);
    form.append('page', page.toString());
    form.append('sort', sort);
    if (taxonomy) {
      form.append('taxonomy', taxonomy);
    }
    if (term) {
      form.append('term', term);
    }

    fetch(pvaSettings.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: form.toString(),
    })
      .then((resp) => resp.json())
      .then((data) => {
        if (!data.success) {
          return;
        }
        if (replace) {
          grid.innerHTML = data.data.html;
        } else {
          grid.insertAdjacentHTML('beforeend', data.data.html);
        }
        initPreviewVideos(grid);
        grid.dataset.page = page.toString();
        grid.dataset.maxPages = data.data.maxPages.toString();

        if (button) {
          if (page >= data.data.maxPages) {
            button.textContent = pvaSettings.noMoreLabel;
            button.disabled = true;
          } else {
            button.textContent = pvaSettings.loadMoreLabel;
            button.disabled = false;
          }
        }
      })
      .catch(() => {
        if (button) {
          button.textContent = pvaSettings.loadMoreLabel;
          button.disabled = false;
        }
      });
  }

  const canHover = window.matchMedia
    ? window.matchMedia('(hover: hover) and (pointer: fine)').matches
    : false;

  const stopPreview = (video) => {
    video.pause();
    try {
      video.currentTime = 0;
    } catch (err) {
      // Ignore browsers that block resetting time before metadata load.
    }
    const card = video.closest('.video-card');
    if (card) {
      card.classList.remove('is-previewing');
    }
  };

  const startPreview = (video) => {
    if (!video.getAttribute('src')) {
      video.setAttribute('src', video.dataset.previewSrc || '');
    }
    const playPromise = video.play();
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {});
    }
    const card = video.closest('.video-card');
    if (card) {
      card.classList.add('is-previewing');
    }
  };

  const getPrimaryCarouselPreview = (track) => {
    const items = Array.from(track.querySelectorAll('.hero-carousel__item'));
    if (!items.length) {
      return null;
    }
    const trackRect = track.getBoundingClientRect();
    let bestItem = null;
    let bestScore = Infinity;
    items.forEach((item) => {
      const rect = item.getBoundingClientRect();
      const overlap = Math.min(rect.right, trackRect.right) - Math.max(rect.left, trackRect.left);
      if (overlap <= 0) {
        return;
      }
      const score = Math.abs(rect.left - trackRect.left);
      if (score < bestScore) {
        bestScore = score;
        bestItem = item;
      }
    });
    if (!bestItem) {
      return null;
    }
    return bestItem.querySelector('.video-card__preview[data-preview-src]');
  };

  const carouselPreviewTimers = new WeakMap();
  const scheduleCarouselPreview = (carousel) => {
    const track = carousel?.querySelector('[data-carousel-track]');
    if (!track) {
      return;
    }
    if (!track.querySelector('.video-card__preview[data-preview-src]')) {
      return;
    }
    const prevTimer = carouselPreviewTimers.get(track);
    if (prevTimer) {
      window.clearTimeout(prevTimer);
    }
    const timerId = window.setTimeout(() => {
      const preview = getPrimaryCarouselPreview(track);
      if (!preview) {
        return;
      }
      track.querySelectorAll('.video-card__preview[data-preview-src]').forEach((video) => {
        if (video !== preview) {
          stopPreview(video);
        }
      });
      startPreview(preview);
    }, 200);
    carouselPreviewTimers.set(track, timerId);
  };

  const carousels = document.querySelectorAll('[data-carousel]');
  carousels.forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const prev = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    if (!track) {
      return;
    }

    const getStep = () => {
      const item = track.querySelector('.hero-carousel__item');
      if (item) {
        const gap = parseFloat(getComputedStyle(track).gap || '0');
        return item.getBoundingClientRect().width + gap;
      }
      return track.clientWidth * 0.9;
    };

    const scrollNext = () => {
      const maxScroll = track.scrollWidth - track.clientWidth;
      if (maxScroll <= 1) {
        return;
      }
      const nextLeft = track.scrollLeft + getStep();
      if (nextLeft >= maxScroll - 2) {
        track.scrollTo({ left: 0, behavior: 'smooth' });
      } else {
        track.scrollBy({ left: getStep(), behavior: 'smooth' });
      }
    };

    const autoplayEnabled = carousel.dataset.carouselAutoplay === 'true';
    const interval = Math.max(1500, parseInt(carousel.dataset.carouselInterval || '4500', 10) || 4500);
    let autoplayId = null;
    let resumeId = null;

    const startAutoplay = () => {
      if (!autoplayEnabled || autoplayId) {
        return;
      }
      autoplayId = window.setInterval(() => {
        if (document.hidden) {
          return;
        }
        scrollNext();
      }, interval);
    };

    const stopAutoplay = () => {
      if (autoplayId) {
        window.clearInterval(autoplayId);
        autoplayId = null;
      }
    };

    const scheduleResume = () => {
      if (!autoplayEnabled) {
        return;
      }
      if (resumeId) {
        window.clearTimeout(resumeId);
      }
      resumeId = window.setTimeout(() => {
        startAutoplay();
      }, interval);
    };

    const handleInteract = () => {
      stopAutoplay();
      scheduleResume();
    };

    prev?.addEventListener('click', () => {
      handleInteract();
      track.scrollBy({ left: -getStep(), behavior: 'smooth' });
      scheduleCarouselPreview(carousel);
    });

    next?.addEventListener('click', () => {
      handleInteract();
      scrollNext();
      scheduleCarouselPreview(carousel);
    });

    track.addEventListener('pointerdown', handleInteract);
    track.addEventListener('touchstart', handleInteract, { passive: true });
    track.addEventListener('wheel', handleInteract, { passive: true });
    track.addEventListener('mouseenter', stopAutoplay);
    track.addEventListener('mouseleave', scheduleResume);
    track.addEventListener('focusin', stopAutoplay);
    track.addEventListener('focusout', scheduleResume);

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopAutoplay();
      } else {
        scheduleResume();
      }
    });

    startAutoplay();
  });

  const initPreviewVideos = (scope = document) => {
    const previewVideos = scope.querySelectorAll('.video-card__preview[data-preview-src]');
    previewVideos.forEach((video) => {
      if (video.dataset.previewBound === 'true') {
        return;
      }
      video.dataset.previewBound = 'true';
      const card = video.closest('.video-card');
      const thumb = video.closest('.video-card__thumb');
      const hoverTarget = card || thumb;
      const src = video.dataset.previewSrc;
      if (!hoverTarget || !src) {
        return;
      }

      const start = () => startPreview(video);

      const stop = () => stopPreview(video);

      if (canHover) {
        hoverTarget.addEventListener('pointerenter', start);
        hoverTarget.addEventListener('pointerleave', stop);
      }
      hoverTarget.addEventListener('mouseenter', start);
      hoverTarget.addEventListener('mouseleave', stop);
      hoverTarget.addEventListener('focusin', start);
      hoverTarget.addEventListener('focusout', stop);
    });
  };

  initPreviewVideos();

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      return;
    }
    document
      .querySelectorAll('.video-card__preview[data-preview-src]')
      .forEach((video) => stopPreview(video));
  });

  const player = document.querySelector('.video-player');
  if (player && window.pvaSettings?.preroll?.enabled) {
    const allowed = player.getAttribute('data-preroll-enabled') === 'true';
    const settings = window.pvaSettings.preroll;
    const overlayEnabled = settings.overlayEnabled !== false;
    const videoEnabled = settings.videoEnabled === true;
    const overlayMediaUrl = settings.imageUrl || settings.mediaUrl || '';
    const videoAds = Array.isArray(settings.videoAds) ? settings.videoAds : [];
    const shouldShowOverlay = overlayEnabled && overlayMediaUrl;
    const shouldShowVideoAds = videoEnabled && videoAds.length > 0;

    if (!allowed || (!shouldShowOverlay && !shouldShowVideoAds)) {
      return;
    }

    const sessionKey = 'pva-preroll-session';
    const timeKey = 'pva-preroll-time';
    const lastTime = parseInt(localStorage.getItem(timeKey) || '0', 10);
    const now = Date.now();
    const minutesSince = (now - lastTime) / 60000;

    if (settings.oncePerSession && sessionStorage.getItem(sessionKey)) {
      return;
    }

    if (!settings.oncePerSession && minutesSince < settings.frequencyMinutes) {
      return;
    }

    const overlay = player.querySelector('.preroll');
    const mediaWrap = player.querySelector('.preroll__media');
    const skipBtn = player.querySelector('.preroll__skip');
    const ctaBtn = player.querySelector('.preroll__cta');
    if (!overlay || !mediaWrap || !skipBtn) {
      return;
    }

    let overlayTimeoutId = null;
    let skipIntervalId = null;

    const markShown = () => {
      localStorage.setItem(timeKey, now.toString());
      if (settings.oncePerSession) {
        sessionStorage.setItem(sessionKey, '1');
      }
    };

    const resetOverlay = () => {
      mediaWrap.innerHTML = '';
      if (overlayTimeoutId) {
        clearTimeout(overlayTimeoutId);
        overlayTimeoutId = null;
      }
      if (skipIntervalId) {
        clearInterval(skipIntervalId);
        skipIntervalId = null;
      }
      skipBtn.disabled = false;
      skipBtn.removeAttribute('disabled');
      skipBtn.textContent = 'ข้ามโฆษณา';
      skipBtn.onclick = null;
      overlay.onclick = null;
    };

    const showOverlay = () => {
      overlay.hidden = false;
      overlay.removeAttribute('hidden');
      overlay.style.display = '';
    };

    const hideOverlay = () => {
      overlay.hidden = true;
      overlay.setAttribute('hidden', '');
      overlay.style.display = 'none';
      resetOverlay();
    };

    const setCta = (targetUrl) => {
      if (!ctaBtn) {
        return;
      }
      if (targetUrl) {
        ctaBtn.setAttribute('href', targetUrl);
        ctaBtn.hidden = false;
        ctaBtn.removeAttribute('hidden');
      } else {
        ctaBtn.hidden = true;
        ctaBtn.setAttribute('hidden', '');
      }
    };

    const enableSkip = (label) => {
      skipBtn.disabled = false;
      skipBtn.removeAttribute('disabled');
      if (label) {
        skipBtn.textContent = label;
      }
    };

    const disableSkip = (label) => {
      skipBtn.disabled = true;
      skipBtn.setAttribute('disabled', 'disabled');
      if (label) {
        skipBtn.textContent = label;
      }
    };

    const renderImage = (mediaUrl, targetUrl) => {
      const img = document.createElement('img');
      img.src = mediaUrl;
      img.alt = 'Advertisement';
      img.addEventListener('error', () => {
        mediaWrap.innerHTML = '<div class="preroll__fallback">ไม่สามารถโหลดโฆษณา</div>';
        enableSkip('ปิดโฆษณา');
      });
      if (targetUrl) {
        const link = document.createElement('a');
        link.href = targetUrl;
        link.appendChild(img);
        mediaWrap.appendChild(link);
      } else {
        mediaWrap.appendChild(img);
      }
    };

    const renderVideo = (mediaUrl, { onEnded, onError } = {}) => {
      const video = document.createElement('video');
      video.src = mediaUrl;
      video.autoplay = true;
      video.muted = true;
      video.playsInline = true;
      video.controls = false;
      if (onEnded) {
        video.addEventListener('ended', onEnded);
      }
      if (onError) {
        video.addEventListener('error', onError);
      }
      mediaWrap.appendChild(video);
      return video;
    };

    const showPictureOverlay = (onDone) => {
      resetOverlay();
      showOverlay();
      setCta(settings.signupUrl || settings.targetUrl || '');
      renderImage(overlayMediaUrl, settings.signupUrl || settings.targetUrl || '');

      if (!settings.closeableOverlay) {
        disableSkip('โฆษณา');
      } else {
        enableSkip('ปิดโฆษณา');
        skipBtn.onclick = () => {
          hideOverlay();
          markShown();
          if (onDone) {
            onDone();
          }
        };
        overlay.onclick = (event) => {
          if (event.target === overlay) {
            hideOverlay();
            markShown();
            if (onDone) {
              onDone();
            }
          }
        };
      }

      overlayTimeoutId = window.setTimeout(() => {
        hideOverlay();
        markShown();
        if (onDone) {
          onDone();
        }
      }, 8000);
    };

    const playVideoAdsSequence = (onComplete) => {
      if (!videoAds.length) {
        if (onComplete) {
          onComplete();
        }
        return;
      }

      let index = 0;
      const next = () => {
        if (index >= videoAds.length) {
          hideOverlay();
          markShown();
          if (onComplete) {
            onComplete();
          }
          return;
        }

        const ad = videoAds[index];
        index += 1;
        const mediaUrl = ad?.mediaUrl || '';
        const targetUrl = ad?.targetUrl || settings.signupUrl || settings.targetUrl || '';
        const skipAfter = parseInt(ad?.skipAfter || 0, 10);
        if (!mediaUrl) {
          next();
          return;
        }

        resetOverlay();
        showOverlay();
        setCta(targetUrl);

        renderVideo(mediaUrl, {
          onEnded: next,
          onError: () => {
            mediaWrap.innerHTML = '<div class="preroll__fallback">ไม่สามารถโหลดโฆษณา</div>';
            enableSkip('ข้ามโฆษณา');
          },
        });

        let remaining = Number.isFinite(skipAfter) ? skipAfter : 0;
        if (remaining <= 0) {
          enableSkip('ข้ามโฆษณา');
        } else {
          disableSkip(`ข้ามใน ${remaining}s`);
          skipIntervalId = window.setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
              clearInterval(skipIntervalId);
              skipIntervalId = null;
              enableSkip('ข้ามโฆษณา');
            } else {
              skipBtn.textContent = `ข้ามใน ${remaining}s`;
            }
          }, 1000);
        }

        skipBtn.onclick = next;
      };

      next();
    };

    const maybePlayMainVideo = (videoEl) => {
      if (!videoEl || typeof videoEl.play !== 'function') {
        return;
      }
      const playPromise = videoEl.play();
      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(() => {});
      }
    };

    const attachVideoGate = () => {
      let started = false;
      const mainVideo = player.querySelector('.sevenls-video-player video');

      const triggerAds = () => {
        if (started) {
          return;
        }
        started = true;
        if (mainVideo) {
          mainVideo.pause();
        }
        playVideoAdsSequence(() => {
          if (mainVideo) {
            maybePlayMainVideo(mainVideo);
          }
        });
      };

      if (mainVideo) {
        mainVideo.addEventListener('play', (event) => {
          if (started) {
            return;
          }
          event.preventDefault();
          mainVideo.pause();
          triggerAds();
        });
      }

      player.addEventListener(
        'pointerdown',
        (event) => {
          if (started) {
            return;
          }
          event.preventDefault();
          if (mainVideo) {
            mainVideo.pause();
          }
          triggerAds();
        },
        { capture: true }
      );
    };

    if (shouldShowOverlay) {
      showPictureOverlay(() => {
        if (shouldShowVideoAds) {
          attachVideoGate();
        }
      });
    } else if (shouldShowVideoAds) {
      attachVideoGate();
    }
  }
})();
