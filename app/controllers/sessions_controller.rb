class SessionsController < ApplicationController
  def new
  end

  def create
    account = Account.find_by(email: session_params[:email])

    if !account&.authenticate(session_params[:password])
      flash.now[:alert] = "Invalid email or password"
      return render :new, status: :unprocessable_entity
    end

    token = account.tokens.create!(
      value: SecureRandom.hex(32),
      expires_at: Time.current + Token::TOKEN_DURATION
    )

    cookies[:auth_token] = {
      value: token.value,
      expires: token.expires_at,
      httponly: true,
      secure: Rails.env.production?,
      domain: :all
    }

    path = session_params[:redirect_to].presence || root_path

    redirect_to path.to_s + "?auth_token=#{token.value}", notice: "Logged in successfully", allow_other_host: true
  end

  def show
    token = Token.find_by(value: params[:token])

    if token.nil? || token.expires_at.past?
      return render json: { error: "Invalid or expired token" }, status: :unauthorized
    end

    render json: token.account.as_json
  end

  private

  def session_params
    params.fetch(:session, {}).permit(:email, :password, :redirect_to)
  end
end
