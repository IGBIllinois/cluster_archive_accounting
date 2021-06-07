#! /usr/bin/env python

#ds3 is for interfacing with the pearl
#argparse is for command line options
from ds3 import ds3
import argparse


parser = argparse.ArgumentParser(description='du style output for the Black Pearl buckets')
parser.add_argument('--size', action='store_true', default=True, help='Print the size of the buckets')
parser.add_argument('--count', action='store_true', default=False, help='Print the number of objects in the buckets')
parser.add_argument('--human', action='store_true', default=False, help="make results human readable")
parser.add_argument('--access', type=str, help='Access key, not needed if environment variables exist')
parser.add_argument('--secret', type=str, help='Secret key, not needed if environment variables exist')
parser.add_argument('--bucket', type=str, help="Bucket Name, only needed if you are only wanting to look at one bucket")
args = parser.parse_args()

    
def human_size(bytes, units=['B','KB','MB','GB','TB', 'PB', 'EB']):
    #Returns a human readable string representation of bytes
    return str(bytes) + " "+units[0] if abs(bytes) < 1024 else human_size(bytes>>10, units[1:])

#gets the number and size of files in a bucket
def BucketStats(_client, _bucket):
  _bucketSize = 0
  _objectCount = 0
  _getMore = True
  _marker = None
  
  #can only retrieve 1000 objects at a time, so we have to retrieve, process, and go again
  while _getMore:
    _resp = _client.get_bucket(ds3.GetBucketRequest(_bucket, None, _marker, 1000))
    for _archiveFile in _resp.result['ContentsList']:
      _bucketSize += int(_archiveFile['Size'])
      _objectCount += 1
    _getMore = _resp.result["IsTruncated"] == 'true'
    _marker = _resp.result["NextMarker"]
  #now that we have all the stats, return them  
  return(_bucketSize, _objectCount)

#prints the bucket, plus the size and or count if requested
#by default, we only print size
#this default is handled in parser
def printResults(_bucket, _bucketSize, _objectCount, _size, _count, _human):
  _pad=15
  if(_size == True and _count == True and _human == True):   
    print(_bucket.ljust(_pad)+"\t"+human_size(_bucketSize)+"\t"+str(_objectCount))
  elif(_size == True and _count == True ):
    print(_bucket.ljust(_pad)+"\t"+str(int(_bucketSize/1024))+"\t"+str(_objectCount))
  elif(_size==True and _human == True):
    print(_bucket.ljust(_pad)+"\t"+human_size(_bucketSize))
  elif(_size==True):
    print(_bucket.ljust(_pad)+"\t"+str(int(_bucketSize/1024)))
  elif(_count==True):
    print(_bucket.ljust(_pad)+"\t"+str(_objectCount))
  else:
    print("No output requested, so none given")
    quit()


#if keys are provided, use those
if(args.access != None and args.secret != None):
  client = ds3.Client("bioarchive.data.igb.illinois.edu",ds3.Credentials(args.access, args.secret))
#if no keys, then try environment variables  
elif(args.access == None and args.secret == None):
  client = ds3.createClientFromEnv()
#if you only have secret or access key, throw an error message  
else:
  print("If not loading from environment, buth --access and --secret are required")
  quit()
  


if(args.bucket == None):
  #get list of buckets
  getServiceResponse = client.get_service(ds3.GetServiceRequest())
  for bucket in getServiceResponse.result['BucketList']:
      #get results and print for each bucket
      (bucketSize, objectCount) = BucketStats(client,bucket['Name'])
      printResults(bucket['Name'], bucketSize, objectCount, args.size, args.count, args.human)
else:
  #get results and print
  (bucketSize, objectCount) = BucketStats(client,args.bucket)
  printResults(args.bucket, bucketSize, objectCount, args.size, args.count, args.human)
