<!-- http://photoswipe.com/documentation/getting-started.html -->

<figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">
  <a itemprop="contentUrl" data-size="{file:width}x{file:height}" href="{file:link}">
   {file:thumbnail}
  </a>
  {if:description_file}
    <figcaption itemprop="caption description"{file:if:is_dir} onclick="window.location='{file:link}'"{end if}>
        {file:filename}
    </figcaption>
  {end if:description_file}
</figure>
