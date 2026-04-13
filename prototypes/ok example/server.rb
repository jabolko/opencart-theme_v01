require 'webrick'

root = File.dirname(File.expand_path(__FILE__))
server = WEBrick::HTTPServer.new(:Port => 8080, :DocumentRoot => root)

trap('INT') { server.shutdown }
trap('TERM') { server.shutdown }

server.start
