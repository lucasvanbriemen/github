class Label < ApplicationRecord
  # Expose the stored hex color (e.g. "d73a4a") as CSS custom properties so the
  # stylesheet can derive a readable foreground and a theme-appropriate
  # background/border from the color's perceived lightness — see the `.label`
  # rules in app/assets/stylesheets/items.scss. This mirrors GitHub's own
  # label-theming approach: a solid swatch in light mode, a translucent and
  # lightened variant in dark mode, with text color always chosen for contrast.
  def style_variables
    r, g, b = rgb
    h, s, l = hsl
    "--label-r:#{r};--label-g:#{g};--label-b:#{b};--label-h:#{h};--label-s:#{s};--label-l:#{l}"
  end

  def rgb
    @rgb ||= color.scan(/../).map { |pair| pair.to_i(16) }
  end

  def hsl
    @hsl ||= begin
      r, g, b = rgb.map { |channel| channel / 255.0 }
      min, max = [ r, g, b ].minmax
      lightness = (max + min) / 2.0
      delta = max - min

      if delta.zero?
        hue = saturation = 0.0
      else
        saturation = delta / (1 - (2 * lightness - 1).abs)
        hue = case max
        when r then ((g - b) / delta) % 6
        when g then ((b - r) / delta) + 2
        else        ((r - g) / delta) + 4
        end
        hue *= 60
      end

      [ hue.round, (saturation * 100).round, (lightness * 100).round ]
    end
  end
end
